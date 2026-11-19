<?php

namespace App\Production\Attendance;

use App\Models\AttendanceDay;
use App\Models\AttendanceMark;
use App\Models\AttendanceShift;
use App\Models\AuditEntry;
use App\Models\Erp\Brigade;
use App\Models\Erp\BrigadeMember;
use App\Models\Erp\Driver;
use App\Models\Staff;
use App\Models\StaffNotification;
use App\Models\TimesheetContract;
use App\Production\Objects\ObjectDirectory;
use App\Production\Rights\Grant;
use App\Production\Rights\Right;
use App\Production\Support\OrgClock;
use App\Production\Support\ProductionException;
use App\Production\Tasks\TaskAccess;
use App\Production\Tasks\TaskFacts;
use App\Production\Tasks\Viewer;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Выход на смену (ТЗ, раздел 7, кроме ТУРВ; П-ЗД-39…45): самоотметка, заявка состава бригадиром, подтверждение в
 * блоке «Выход» задания, водители. Права проверяет сервер в момент получения (п. 9.1, правило 4)
 */
final class AttendanceService
{
    /** @var array<int, array{id: int, name: ?string}> ФИО в пределах запроса */
    private array $names = [];

    public function __construct(
        private readonly OrgClock $clock,
        private readonly TaskAccess $access,
        private readonly AttendanceNotices $notices,
    ) {
    }

    // ---- Кто отмечается ----

    /**
     * Бригады сотрудника на дату: строки состава ERP и бригадир (П-ОБ-16)
     *
     * @return array<int, string> id => название
     */
    public function brigadesOf(int $staffId, CarbonInterface $date): array
    {
        $ids = BrigadeMember::activeOn($date)->where('staff_id', $staffId)->pluck('brigade_id')
            ->merge(Brigade::where('foreman_staff_id', $staffId)->pluck('id'))
            ->unique()->values()->all();

        return $ids ? Brigade::whereIn('id', $ids)->workingOn($date)->orderBy('name')->pluck('name', 'id')->all() : [];
    }

    /** В списке водителей ERP и не «не используется» */
    public function isDriver(int $staffId): bool
    {
        return Driver::where('staff_id', $staffId)->where('not_used', false)->exists();
    }

    // ---- Плашка «Выход на смену» (п. 7.4) ----

    public function today(Viewer $viewer): array
    {
        $now = $this->clock->now();
        $today = $now->startOfDay();
        $day = $today->toDateString();
        $brigades = $this->brigadesOf($viewer->staffId, $today);
        $isDriver = $this->isDriver($viewer->staffId);
        $data = AttendanceData::forDates([$day]);
        $mark = $data->markOf($viewer->staffId, $day);
        $canDeclare = $viewer->rights->has(Right::CrewDeclare);
        $members = Brigade::currentStaff(array_keys($brigades), $today);

        return [
            'date' => $day,
            'now' => $now->toIso8601String(),
            'eligible' => $brigades !== [] || $isDriver,
            'driver' => $isDriver,
            'window' => ['from' => AttendanceRules::MARK_FROM, 'to' => AttendanceRules::MARK_TO, 'open' => AttendanceRules::markWindowOpen($now)],
            'mark' => $mark ? $this->markItem($mark, $brigades) : null,
            'can_declare' => $canDeclare,
            'brigades' => collect($brigades)->map(function (string $name, int $id) use ($data, $day, $members, $canDeclare, $viewer) {
                $shift = $data->shift($id, $day);
                $crew = $shift?->crew();
                // До подтверждения — видимый состав (П-СМ-08), после — подтверждённый
                $marked = ($shift?->isConfirmed() ? $data->brigadeDays($id, $day) : $data->visibleCrew($id, $day))
                    ->pluck('staff_id')->map(fn ($s) => (int) $s)->all();

                return [
                    'id' => $id,
                    'name' => $name,
                    'crew' => $this->people($marked),
                    'declared' => $shift && $crew !== null ? ['by' => $this->person((int) $shift->crew_by_staff_id), 'at' => $shift->crew_at?->toIso8601String(), 'staff_ids' => $crew] : null,
                    'confirmed' => $shift?->isConfirmed() ? ['by' => $this->person((int) $shift->confirmed_by_staff_id), 'at' => $shift->confirmed_at->toIso8601String()] : null,
                    // Для заявки: члены бригады с отметками, кто сегодня отметился (п. 7.4)
                    'members' => $canDeclare ? collect($members[$id] ?? [])->map(fn (int $staffId) => $this->person($staffId) + [
                        'marked' => (int) ($data->markOf($staffId, $day)?->brigade_id ?? 0) === $id,
                    ])->sortBy('name')->values()->all() : [],
                ];
            })->values()->all(),
        ];
    }

    // ---- Самоотметка (П-СМ-01…05, П-СМ-20, П-СМ-20а) ----

    public function mark(Viewer $viewer, array $input): AttendanceMark
    {
        $uuid = $input['client_uuid'] ?? null;
        if ($uuid && $existing = AttendanceMark::where('client_uuid', $uuid)->first()) {
            if ((int) $existing->staff_id !== $viewer->staffId) {
                throw ProductionException::invalid('Отметка не принята');
            }

            return $existing;
        }
        $serverNow = $this->clock->now();
        $time = AttendanceRules::actionTime((bool) ($input['offline'] ?? false), $input['pressed_at'] ?? null, $input['device_now'] ?? null, $serverNow);
        $at = $time['at'];
        $date = $at->startOfDay();
        if ($date->toDateString() > $serverNow->toDateString() || !AttendanceRules::markWindowOpen($at)) {
            throw ProductionException::invalid('Отметка — с '.AttendanceRules::MARK_FROM.' до '.AttendanceRules::MARK_TO, 'mark_window_closed');
        }

        $brigades = $this->brigadesOf($viewer->staffId, $date);
        $isDriver = $this->isDriver($viewer->staffId);
        if (!$brigades && !$isDriver) {
            throw ProductionException::forbidden('Вы не состоите в бригаде и не входите в список водителей');
        }
        $asDriver = isset($input['as_driver']) ? (bool) $input['as_driver'] : ($brigades === []);
        if ($asDriver && !$isDriver) {
            throw ProductionException::invalid('Вас нет в списке водителей');
        }
        if (!isset($input['as_driver']) && $brigades && $isDriver) {
            throw ProductionException::invalid('Выберите: в бригаде или водителем', 'choose_kind');
        }
        $brigadeId = null;
        if (!$asDriver) {
            $brigadeId = isset($input['brigade_id']) ? (int) $input['brigade_id'] : (count($brigades) === 1 ? (int) array_key_first($brigades) : null);
            if ($brigadeId === null) {
                throw ProductionException::invalid('Выберите бригаду', 'choose_brigade');
            }
            if (!isset($brigades[$brigadeId])) {
                throw ProductionException::invalid('Вы не член бригады', 'not_member');
            }
        }

        [$mark, $shift] = DB::transaction(function () use ($viewer, $date, $asDriver, $brigadeId, $time, $serverNow, $uuid) {
            if (AttendanceMark::where('staff_id', $viewer->staffId)->where('work_date', $date->toDateString())->exists()) {
                throw ProductionException::invalid('Вы уже отметились', 'already_marked');
            }
            $shift = $brigadeId ? $this->lockShift($brigadeId, $date) : null;
            try {
                $mark = AttendanceMark::create([
                    'staff_id' => $viewer->staffId,
                    'work_date' => $date->toDateString(),
                    'as_driver' => $asDriver,
                    'brigade_id' => $brigadeId,
                    'pressed_at' => $time['at'],
                    'received_at' => $serverNow,
                    'offline' => $time['offline'],
                    'time_unverified' => $time['unverified'],
                    'after_confirmation' => (bool) $shift?->isConfirmed(),
                    'client_uuid' => $uuid,
                ]);
            } catch (QueryException $error) {
                if ((int) ($error->errorInfo[1] ?? 0) === 1062) {
                    throw ProductionException::invalid('Вы уже отметились', 'already_marked');
                }
                throw $error;
            }
            $this->audit(AuditEntry::ENTITY_ATTENDANCE_MARK, $mark->id, 'marked', $viewer->staffId, null, [
                'date' => $date->toDateString(), 'as_driver' => $asDriver, 'brigade_id' => $brigadeId,
                'pressed_at' => $time['at']->toIso8601String(), 'offline' => $time['offline'], 'time_unverified' => $time['unverified'],
                'after_confirmation' => $mark->after_confirmation,
            ]);

            return [$mark, $shift];
        });

        $day = $date->toDateString();
        $me = $this->person($viewer->staffId)['name'];
        if ($shift && $mark->after_confirmation) {
            // Отметка без связи пришла после подтверждения: состав не меняется, подтвердившему — сигнал (П-СМ-03а)
            $this->notices->send((int) $shift->confirmed_by_staff_id, StaffNotification::KIND_MARK_LATE, 'Отметка после подтверждения',
                sprintf('%s, бригада «%s», %s', $me, $this->brigadeName($brigadeId), $this->date($day)), ['date' => $day, 'brigade_id' => $brigadeId, 'staff_id' => $viewer->staffId]);
        } elseif ($shift && $shift->crew() !== null && !in_array($viewer->staffId, $shift->crew(), true)) {
            $this->notifyNotIncluded($viewer->staffId, $shift);
        }
        if ($asDriver) {
            $this->syncDriverNotices($viewer->staffId, $date);
        }

        return $mark;
    }

    // ---- Заявка состава (право 16; П-СМ-07, П-СМ-07а, П-СМ-09) ----

    /** @param int[] $staffIds */
    public function declareCrew(Viewer $viewer, int $brigadeId, array $staffIds, array $input): AttendanceShift
    {
        if (!$viewer->rights->has(Right::CrewDeclare)) {
            throw ProductionException::forbidden('Нет права «Заявлять состав бригады»');
        }
        $serverNow = $this->clock->now();
        $time = AttendanceRules::actionTime((bool) ($input['offline'] ?? false), $input['pressed_at'] ?? null, $input['device_now'] ?? null, $serverNow);
        $date = $time['at']->startOfDay();
        if ($date->toDateString() > $serverNow->toDateString() || !AttendanceRules::crewWindowOpen($time['at'])) {
            throw ProductionException::invalid('Заявить состав можно с '.AttendanceRules::MARK_FROM, 'crew_window_closed');
        }
        if ($date->toDateString() < $serverNow->toDateString() && !$time['offline']) {
            throw ProductionException::invalid('Заявить состав можно только на сегодня', 'crew_window_closed');
        }
        if (!isset($this->brigadesOf($viewer->staffId, $date)[$brigadeId])) {
            throw ProductionException::invalid('Вы не член бригады', 'not_member');
        }
        $members = Brigade::currentStaff([$brigadeId], $date)[$brigadeId] ?? [];
        $staffIds = array_values(array_unique(array_map('intval', $staffIds)));
        if (array_diff($staffIds, $members)) {
            throw ProductionException::invalid('В заявке есть не член бригады');
        }

        try {
            $shift = DB::transaction(function () use ($viewer, $brigadeId, $date, $staffIds) {
                $shift = $this->lockShift($brigadeId, $date);
                if ($shift->isConfirmed()) {
                    throw ProductionException::invalid('Выход уже подтверждён: '.$this->person((int) $shift->confirmed_by_staff_id)['name'], 'already_confirmed');
                }
                if ($shift->crew_by_staff_id && (int) $shift->crew_by_staff_id !== $viewer->staffId) {
                    throw ProductionException::invalid('Состав уже заявлен: '.$this->person((int) $shift->crew_by_staff_id)['name'], 'crew_declared');
                }
                $before = $shift->crew();
                sort($staffIds);
                $shift->update(['crew_staff_ids' => $staffIds, 'crew_by_staff_id' => $viewer->staffId, 'crew_at' => now()]);
                if ($before !== $staffIds) {
                    $this->audit(AuditEntry::ENTITY_ATTENDANCE_SHIFT, $shift->id, $before === null ? 'crew_declared' : 'crew_changed', $viewer->staffId,
                        $before === null ? null : ['staff_ids' => $before], ['brigade_id' => $brigadeId, 'date' => $date->toDateString(), 'staff_ids' => $staffIds]);
                }

                return $shift;
            });
        } catch (ProductionException $error) {
            $this->rejectedOffline($viewer->staffId, (bool) ($input['offline'] ?? false), 'Заявка состава не принята', $error);
            throw $error;
        }

        // Отметился, но не включён — push и плашка; включили — плашка снимается (П-СМ-09)
        foreach ($this->marksOfBrigade($brigadeId, $date) as $mark) {
            $staffId = (int) $mark->staff_id;
            if (in_array($staffId, $staffIds, true)) {
                $this->notices->withdraw($staffId, StaffNotification::KIND_CREW_NOT_INCLUDED, ['shift_id' => $shift->id]);
            } elseif (!$this->notices->open($staffId, StaffNotification::KIND_CREW_NOT_INCLUDED, ['shift_id' => $shift->id])) {
                $this->notifyNotIncluded($staffId, $shift);
            }
        }

        return $shift->fresh();
    }

    // ---- Подтверждение в блоке «Выход» (П-СМ-10…14, П-СМ-21, П-ЗД-39…43) ----

    /**
     * @param int[]|null $staffIds отмеченные в составе бригады; null — состав бригады не подтверждается
     * @return array блок «Выход» после подтверждения
     */
    public function confirm(Viewer $viewer, TimesheetContract $task, ?array $staffIds, bool $driver, array $input): array
    {
        $offline = (bool) ($input['offline'] ?? false);
        $serverNow = $this->clock->now();
        $today = $serverNow->startOfDay();
        $time = AttendanceRules::actionTime($offline, $input['pressed_at'] ?? null, $input['device_now'] ?? null, $serverNow);
        $facts = TaskFacts::fromModel($task);
        $date = $facts->date;
        $day = $date->toDateString();
        $data = AttendanceData::forDates([$day]);
        $brigadeId = $this->brigadeOfTask($facts, $data);

        if ($staffIds === null && !$driver) {
            throw ProductionException::invalid('Нечего подтверждать');
        }
        if (!AttendanceRules::confirmTimeAllows($date, $time['at'])) {
            throw ProductionException::invalid('Подтвердить можно с '.AttendanceRules::CONFIRM_FROM, 'confirm_too_early');
        }

        try {
            $notify = DB::transaction(function () use ($viewer, $facts, $date, $day, $brigadeId, $staffIds, $driver, $offline, $time, $today, $input) {
                $notify = null;
                if ($staffIds !== null) {
                    if ($brigadeId === null) {
                        throw ProductionException::invalid('В задании нет состава бригады');
                    }
                    if (!$this->canConfirm($viewer, $facts, $brigadeId, $today)) {
                        throw ProductionException::forbidden('Нет права подтверждать этот выход');
                    }
                    $notify = $this->confirmBrigade($viewer, $brigadeId, $date, array_values(array_unique(array_map('intval', $staffIds))), $offline, $time['unverified'],
                        isset($input['shown_staff_ids']) ? array_map('intval', (array) $input['shown_staff_ids']) : null);
                }
                if ($driver) {
                    $driverStaffId = $facts->driverStaffId ?? throw ProductionException::invalid('В задании нет водителя');
                    if (!$this->canConfirm($viewer, $facts, null, $today)) {
                        throw ProductionException::forbidden('Нет права подтверждать этот выход');
                    }
                    $this->confirmDriver($viewer, $driverStaffId, $day, $staffIds === null);
                }

                return $notify;
            });
        } catch (ProductionException $error) {
            $this->rejectedOffline($viewer->staffId, $offline, 'Подтверждение не применено', $error, (int) $task->id);
            throw $error;
        }
        if ($notify) {
            $notify();
        }

        return $this->block($viewer, $task->fresh());
    }

    // ---- Блок «Выход» в карточке задания и значок неподтверждённых (П-ЗД-39, П-ЗД-44, П-СМ-19) ----

    public function block(Viewer $viewer, TimesheetContract $task, ?AttendanceData $data = null): ?array
    {
        $facts = TaskFacts::fromModel($task);
        $day = $facts->date->toDateString();
        $data ??= AttendanceData::forDates([$day]);
        $now = $this->clock->now();
        $today = $now->startOfDay();
        $brigadeId = $this->brigadeOfTask($facts, $data);
        $driverStaffId = $facts->driverStaffId;
        if ($brigadeId === null && $driverStaffId === null) {
            return null;
        }
        $canBrigade = $brigadeId !== null && $this->canConfirm($viewer, $facts, $brigadeId, $today);
        $canDriver = $driverStaffId !== null && $this->canConfirm($viewer, $facts, null, $today);
        // Пометки «сделана без связи», «время не проверено» и заявку бригадира видит только тот, кто вправе подтверждать
        $service = $canBrigade || $canDriver;
        $timeAllows = AttendanceRules::confirmTimeAllows($facts->date, $now);

        $brigade = null;
        if ($brigadeId !== null) {
            $shift = $data->shift($brigadeId, $day);
            $confirmed = (bool) $shift?->isConfirmed();
            $crewIds = $shift?->crew();
            $people = $confirmed
                ? $data->brigadeDays($brigadeId, $day)->map(fn (AttendanceDay $d) => $this->person((int) $d->staff_id))
                : $data->visibleCrew($brigadeId, $day)->map(fn (AttendanceMark $m) => $this->person((int) $m->staff_id) + ($service ? [
                    'offline' => $m->offline && !$m->time_unverified,
                    'time_unverified' => $m->time_unverified,
                    'declared' => $crewIds !== null && in_array((int) $m->staff_id, $crewIds, true),
                ] : []));
            $brigade = [
                'id' => $brigadeId,
                'name' => $this->brigadeName($brigadeId),
                'crew' => $people->sortBy('name')->values()->all(),
                'status' => $confirmed ? 'confirmed' : 'unconfirmed',
                'confirmed_by' => $confirmed ? $this->person((int) $shift->confirmed_by_staff_id) : null,
                'confirmed_at' => $confirmed ? $shift->confirmed_at->toIso8601String() : null,
                'declared_by' => $service && $crewIds !== null ? $this->person((int) $shift->crew_by_staff_id) : null,
                'can_confirm' => $canBrigade && !$confirmed && $timeAllows,
            ];
        }

        $driverPart = null;
        if ($driverStaffId !== null) {
            $mark = $data->markOf($driverStaffId, $day);
            $dayRow = $data->dayOf($driverStaffId, $day);
            $status = match (true) {
                $mark === null => 'not_marked',
                !$mark->as_driver => 'in_brigade',
                $dayRow !== null && $dayRow->as_driver => 'confirmed',
                default => 'unconfirmed',
            };
            $driverPart = $this->person($driverStaffId) + [
                'status' => $status,
                'confirmed_by' => $status === 'confirmed' ? $this->person((int) $dayRow->by_staff_id) : null,
                'can_confirm' => $canDriver && $status === 'unconfirmed' && $timeAllows,
            ] + ($service && $mark ? ['offline' => $mark->offline && !$mark->time_unverified, 'time_unverified' => $mark->time_unverified] : []);
        }

        return [
            'date' => $day,
            'brigade' => $brigade,
            'driver' => $driverPart,
            // Сегодня до 10:00 — кнопка есть, но подтвердить ещё нельзя
            'confirm_from' => ($canBrigade || $canDriver) && !$timeAllows && $day === $today->toDateString() ? AttendanceRules::CONFIRM_FROM : null,
            'badge' => $this->badge($viewer, $facts, $data, $now),
        ];
    }

    /**
     * Значки неподтверждённых выходов по заданиям календаря (П-СМ-19): один набор запросов на весь список
     *
     * @param Collection<int, TimesheetContract> $tasks
     * @return array<int, bool> task_id => значок
     */
    public function badges(Viewer $viewer, Collection $tasks): array
    {
        if ($tasks->isEmpty()) {
            return [];
        }
        $now = $this->clock->now();
        $facts = $tasks->mapWithKeys(fn (TimesheetContract $t) => [(int) $t->id => TaskFacts::fromModel($t, true)]);
        $data = AttendanceData::forDates($facts->map(fn (TaskFacts $f) => $f->date->toDateString())->values()->all());

        return $facts->map(fn (TaskFacts $f) => $this->badge($viewer, $f, $data, $now))->all();
    }

    // ---- Водитель: «нет заданий» и «вы назначены в задание» (П-СМ-22) ----

    /**
     * Сообщения водителю, отметившемуся водителем: нет заданий на дату — push и сообщение; назначили после этого —
     * «вы назначены в задание»; убрали из последнего задания — снова «нет заданий»
     */
    public function syncDriverNotices(int $driverStaffId, CarbonInterface $date): void
    {
        $day = $date->toDateString();
        $mark = AttendanceMark::where('staff_id', $driverStaffId)->where('work_date', $day)->where('as_driver', true)->exists();
        if (!$mark) {
            return;
        }
        $taskId = TimesheetContract::query()
            ->join('timesheet_teams as t', 't.id', '=', 'timesheet_contracts.team_id')
            ->where('timesheet_contracts.driver_staff_id', $driverStaffId)
            ->where('t.day', $day)
            ->orderBy('timesheet_contracts.id')
            ->value('timesheet_contracts.id');
        // Последнее сообщение по этой дате (открытое или закрытое) — в каком состоянии водитель сейчас
        $last = StaffNotification::where('staff_id', $driverStaffId)
            ->whereIn('kind', [StaffNotification::KIND_DRIVER_NO_TASKS, StaffNotification::KIND_DRIVER_ASSIGNED])
            ->where('payload->date', $day)
            ->latest('id')->first();

        if ($taskId === null) {
            if ($last?->kind === StaffNotification::KIND_DRIVER_NO_TASKS) {
                return;
            }
            $this->notices->withdraw($driverStaffId, StaffNotification::KIND_DRIVER_ASSIGNED, ['date' => $day]);
            $this->notices->send($driverStaffId, StaffNotification::KIND_DRIVER_NO_TASKS, 'Нет заданий', $this->date($day), ['date' => $day], push: true);

            return;
        }
        if ($last?->kind !== StaffNotification::KIND_DRIVER_NO_TASKS) {
            return;
        }
        $this->notices->withdraw($driverStaffId, StaffNotification::KIND_DRIVER_NO_TASKS, ['date' => $day]);
        $object = DB::table('timesheet_contracts as c')->join('contracts as o', 'o.id', '=', 'c.contract_id')->where('c.id', $taskId)->first(['o.object_number', 'o.title']);
        $this->notices->send($driverStaffId, StaffNotification::KIND_DRIVER_ASSIGNED, 'Вы назначены в задание',
            sprintf('Объект %s «%s», %s', $object?->object_number, $object?->title, $this->date($day)), ['date' => $day, 'task_id' => (int) $taskId], taskId: (int) $taskId);
    }

    // ---- Внутреннее ----

    /** Может ли сотрудник подтверждать: создатель — сегодня и 3 дня назад; право 17 — глубина и бригады (П-СМ-10а) */
    private function canConfirm(Viewer $viewer, TaskFacts $facts, ?int $brigadeId, CarbonInterface $today): bool
    {
        if (!$this->access->canView($viewer, $facts)) {
            return false;
        }
        if ($facts->creatorId === $viewer->staffId && AttendanceRules::creatorDaysAllow($facts->date, $today)) {
            return true;
        }

        // Право 17 на водителя действует без перечня бригад (П-ПР-19б)
        return $viewer->rights->any(Right::AttendanceConfirmOthers, fn (Grant $g) => $g->allowsDate($facts->date, $today)
            && ($brigadeId === null || $g->coversBrigade($brigadeId)));
    }

    private function badge(Viewer $viewer, TaskFacts $facts, AttendanceData $data, CarbonImmutable $now): bool
    {
        if (!AttendanceRules::badgeTimeAllows($facts->date, $now)) {
            return false;
        }
        $day = $facts->date->toDateString();
        $today = $now->startOfDay();
        $brigadeId = $this->brigadeOfTask($facts, $data);
        if ($brigadeId !== null && !$data->shift($brigadeId, $day)?->isConfirmed() && $data->visibleCrew($brigadeId, $day)->isNotEmpty()
            && $this->canConfirm($viewer, $facts, $brigadeId, $today)) {
            return true;
        }
        if ($facts->driverStaffId !== null) {
            $mark = $data->markOf($facts->driverStaffId, $day);
            $confirmed = $data->dayOf($facts->driverStaffId, $day)?->as_driver;

            return $mark !== null && $mark->as_driver && !$confirmed && $this->canConfirm($viewer, $facts, null, $today);
        }

        return false;
    }

    /** Бригада, чей выход в задании: исполнитель-бригада или бригада, где отметился личный исполнитель (П-СМ-10в) */
    private function brigadeOfTask(TaskFacts $facts, AttendanceData $data): ?int
    {
        if ($facts->brigadeId !== null) {
            return $facts->brigadeId;
        }
        $mark = $facts->personalStaffId ? $data->markOf($facts->personalStaffId, $facts->date->toDateString()) : null;

        return $mark && !$mark->as_driver && $mark->brigade_id ? (int) $mark->brigade_id : null;
    }

    /**
     * @param int[]|null $shown список, который подтверждающий видел без связи (п. 9.1, правило 7)
     * @return callable|null уведомления — после фиксации транзакции
     */
    private function confirmBrigade(Viewer $viewer, int $brigadeId, CarbonImmutable $date, array $staffIds, bool $offline, bool $unverified, ?array $shown): ?callable
    {
        $day = $date->toDateString();
        $shift = $this->lockShift($brigadeId, $date);
        if ($shift->isConfirmed()) {
            if ((int) $shift->confirmed_by_staff_id === $viewer->staffId && $offline) {
                return null; // повторная отправка того же подтверждения из очереди
            }
            throw ProductionException::invalid('Выход уже подтверждён: '.$this->person((int) $shift->confirmed_by_staff_id)['name'], 'already_confirmed');
        }
        $visible = AttendanceData::forDates([$day])->visibleCrew($brigadeId, $day)->pluck('staff_id')->map(fn ($id) => (int) $id)->all();
        // Без связи — тот список, что был на экране; отметки, которых в нём не было, пришли «после подтверждения»
        $seen = $offline && $shown !== null ? array_values(array_intersect($visible, $shown)) : $visible;
        if (array_diff($staffIds, $seen)) {
            throw ProductionException::invalid('Добавить в состав нельзя — только снять', 'not_in_crew');
        }

        $shift->update(['confirmed_by_staff_id' => $viewer->staffId, 'confirmed_at' => now(), 'confirmed_offline' => $offline]);
        foreach ($staffIds as $staffId) {
            // Подтверждённо вышедший в другой бригаде на эту дату второй раз не засчитывается (П-СМ-17)
            if (!AttendanceDay::where('staff_id', $staffId)->where('work_date', $day)->exists()) {
                $this->createDay($staffId, $day, false, $brigadeId, $shift->id, $viewer->staffId);
            }
        }
        $late = array_values(array_diff($visible, $seen));
        if ($late) {
            AttendanceMark::whereIn('staff_id', $late)->where('work_date', $day)->update(['after_confirmation' => true]);
        }
        $excluded = array_values(array_diff($seen, $staffIds));
        $this->audit(AuditEntry::ENTITY_ATTENDANCE_SHIFT, $shift->id, 'confirmed', $viewer->staffId, ['staff_ids' => $seen], [
            'brigade_id' => $brigadeId, 'date' => $day, 'staff_ids' => $staffIds, 'excluded' => $excluded,
            'offline' => $offline, 'time_unverified' => $unverified,
        ]);

        $by = $this->person($viewer->staffId)['name'];

        return function () use ($excluded, $late, $shift, $brigadeId, $day, $by, $viewer) {
            foreach ($excluded as $staffId) {
                // Исключённый при подтверждении — push и переносимая плашка с тем, кто подтвердил (П-СМ-14)
                $this->notices->send($staffId, StaffNotification::KIND_CREW_EXCLUDED, 'Вас исключили из состава',
                    sprintf('Бригада «%s», %s. Подтвердил: %s', $this->brigadeName($brigadeId), $this->date($day), $by),
                    ['shift_id' => $shift->id, 'date' => $day, 'brigade_id' => $brigadeId], push: true);
            }
            foreach ($late as $staffId) {
                $this->notices->send($viewer->staffId, StaffNotification::KIND_MARK_LATE, 'Отметка после подтверждения',
                    sprintf('%s, бригада «%s», %s', $this->person($staffId)['name'], $this->brigadeName($brigadeId), $this->date($day)),
                    ['date' => $day, 'brigade_id' => $brigadeId, 'staff_id' => $staffId]);
            }
        };
    }

    private function confirmDriver(Viewer $viewer, int $driverStaffId, string $day, bool $only): void
    {
        $mark = AttendanceMark::where('staff_id', $driverStaffId)->where('work_date', $day)->lockForUpdate()->first();
        if (!$mark || !$mark->as_driver) {
            throw ProductionException::invalid($mark ? 'Водитель отметился в бригаде' : 'Водитель не отметился', 'driver_not_marked');
        }
        $existing = AttendanceDay::where('staff_id', $driverStaffId)->where('work_date', $day)->first();
        if ($existing) {
            if ($only && (int) $existing->by_staff_id !== $viewer->staffId) {
                throw ProductionException::invalid('Выход уже подтверждён: '.$this->person((int) $existing->by_staff_id)['name'], 'already_confirmed');
            }

            return;
        }
        $this->createDay($driverStaffId, $day, true, null, null, $viewer->staffId);
    }

    private function createDay(int $staffId, string $day, bool $asDriver, ?int $brigadeId, ?int $shiftId, int $byStaffId): void
    {
        $row = AttendanceDay::create([
            'staff_id' => $staffId,
            'work_date' => $day,
            'as_driver' => $asDriver,
            'brigade_id' => $brigadeId,
            'source' => AttendanceDay::SOURCE_CONFIRMED,
            'shift_id' => $shiftId,
            'by_staff_id' => $byStaffId,
        ]);
        $this->audit(AuditEntry::ENTITY_ATTENDANCE_DAY, $row->id, $asDriver ? 'driver_confirmed' : 'confirmed', $byStaffId, null,
            ['staff_id' => $staffId, 'date' => $day, 'as_driver' => $asDriver, 'brigade_id' => $brigadeId]);
    }

    /** Смена бригады на дату, заблокированная до конца транзакции; создаётся при первом действии (П-СМ-02) */
    private function lockShift(int $brigadeId, CarbonInterface $date): AttendanceShift
    {
        $day = $date->toDateString();
        $shift = AttendanceShift::where('brigade_id', $brigadeId)->where('work_date', $day)->lockForUpdate()->first();
        if ($shift) {
            return $shift;
        }
        try {
            return AttendanceShift::create(['brigade_id' => $brigadeId, 'work_date' => $day]);
        } catch (QueryException $error) {
            if ((int) ($error->errorInfo[1] ?? 0) !== 1062) {
                throw $error;
            }

            return AttendanceShift::where('brigade_id', $brigadeId)->where('work_date', $day)->lockForUpdate()->firstOrFail();
        }
    }

    /** @return Collection<int, AttendanceMark> */
    private function marksOfBrigade(int $brigadeId, CarbonInterface $date): Collection
    {
        return AttendanceMark::where('brigade_id', $brigadeId)->where('work_date', $date->toDateString())
            ->where('as_driver', false)->where('after_confirmation', false)->get();
    }

    /** Отметился, но бригадир не включил в заявку — push и плашка до закрытия (П-СМ-09) */
    private function notifyNotIncluded(int $staffId, AttendanceShift $shift): void
    {
        $day = $shift->work_date->toDateString();
        $this->notices->send($staffId, StaffNotification::KIND_CREW_NOT_INCLUDED, 'Вас нет в заявке состава',
            sprintf('Бригада «%s», %s. Заявил: %s', $this->brigadeName((int) $shift->brigade_id), $this->date($day), $this->person((int) $shift->crew_by_staff_id)['name']),
            ['shift_id' => $shift->id, 'date' => $day, 'brigade_id' => (int) $shift->brigade_id], push: true);
    }

    /** Заявка или подтверждение без связи не приняты — автору сообщение с причиной (П-СМ-07а, П-СМ-13а) */
    private function rejectedOffline(int $staffId, bool $offline, string $title, ProductionException $error, ?int $taskId = null): void
    {
        if ($offline && in_array($error->reason, ['already_confirmed', 'crew_declared'], true)) {
            $this->notices->send($staffId, StaffNotification::KIND_ATTENDANCE_REJECTED, $title, $error->getMessage(), ['reason' => $error->reason], taskId: $taskId);
        }
    }

    private function markItem(AttendanceMark $mark, array $brigades): array
    {
        return [
            'as_driver' => $mark->as_driver,
            'brigade_id' => $mark->brigade_id ? (int) $mark->brigade_id : null,
            'brigade_name' => $mark->brigade_id ? ($brigades[(int) $mark->brigade_id] ?? $this->brigadeName((int) $mark->brigade_id)) : null,
            'pressed_at' => $mark->pressed_at?->toIso8601String(),
        ];
    }

    private function audit(string $entity, int $id, string $action, int $actor, ?array $before, ?array $after): void
    {
        AuditEntry::create([
            'entity_type' => $entity,
            'entity_id' => $id,
            'action' => $action,
            'actor_staff_id' => $actor,
            'before' => $before,
            'after' => $after,
        ]);
    }

    /** @param int[] $staffIds */
    private function people(array $staffIds): array
    {
        return collect($staffIds)->map(fn (int $id) => $this->person($id))->sortBy('name')->values()->all();
    }

    private function person(int $staffId): array
    {
        if (!isset($this->names[$staffId])) {
            $staff = Staff::query()->select(['id', 'sname', 'fname', 'mname'])->find($staffId);
            $this->names[$staffId] = ['id' => $staffId, 'name' => $staff ? ObjectDirectory::name($staff->sname, $staff->fname, $staff->mname) : null];
        }

        return $this->names[$staffId];
    }

    private function brigadeName(?int $brigadeId): ?string
    {
        return $brigadeId ? Brigade::whereKey($brigadeId)->value('name') : null;
    }

    private function date(string $day): string
    {
        return CarbonImmutable::parse($day)->format('d.m.Y');
    }
}
