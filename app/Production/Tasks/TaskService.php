<?php

namespace App\Production\Tasks;

use App\Models\AuditEntry;
use App\Models\Erp\Brigade;
use App\Models\Erp\BrigadeMember;
use App\Models\Erp\ContractPlanRevision;
use App\Models\Erp\Driver;
use App\Models\Erp\Vehicle;
use App\Models\Staff;
use App\Models\StaffNotification;
use App\Models\TimesheetContract;
use App\Models\TimesheetContractInvite;
use App\Models\TimesheetTeam;
use App\Production\Attendance\AttendanceService;
use App\Production\Objects\ObjectDirectory;
use App\Production\Reports\ReportService;
use App\Production\Rights\Right;
use App\Production\Support\ProductionException;
use App\Services\AccountAccess;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/** Действия с заданиями (ТЗ, раздел 6) */
final class TaskService
{
    public function __construct(
        private readonly TaskAccess $access,
        private readonly TaskPresenter $presenter,
        private readonly ObjectDirectory $objects,
        private readonly AccountAccess $accounts,
        private readonly AttendanceService $attendance,
        private readonly ReportService $reports,
    ) {
    }

    /** Задания календаря за период — только те, что сотрудник видит (П-ЗД-20), каждое один раз */
    public function calendar(Viewer $viewer, CarbonImmutable $from, CarbonImmutable $to, array $filters): Builder
    {
        $query = (new TaskVisibility())->query($viewer)
            ->whereBetween('t.day', [$from->toDateString(), $to->toDateString()]);

        foreach ([
            'object_id' => 'timesheet_contracts.contract_id',
            'brigade_id' => 't.brigade_id',
            'creator_id' => 'timesheet_contracts.creator_staff_id',
            'vehicle_id' => 'timesheet_contracts.vehicle_id',
            'driver_staff_id' => 'timesheet_contracts.driver_staff_id',
        ] as $filter => $column) {
            if (isset($filters[$filter])) {
                $query->where($column, (int) $filters[$filter]);
            }
        }
        if (isset($filters['executor_staff_id'])) {
            $query->whereNull('t.brigade_id')->where('t.staff_id', (int) $filters['executor_staff_id']);
        }

        return $query->orderBy('t.day')->orderBy('timesheet_contracts.id');
    }

    /** Видимое задание или 404: существование чужого задания не раскрывается */
    public function visible(Viewer $viewer, int $taskId): TimesheetContract
    {
        $task = TimesheetContract::find($taskId);
        if (!$task || !$this->access->canView($viewer, TaskFacts::fromModel($task))) {
            throw ProductionException::notFound();
        }

        return $task;
    }

    /**
     * Создать задание (право 1, ИС-ЗД-01): объект, дата и исполнитель обязательны; содержание — текст и/или виды работ
     * из плана объекта; техника с водителем необязательна (П-ЗД-11, П-ОБ-24).
     *
     * @return array{0: TimesheetContract, 1: array} задание и предупреждения
     */
    public function create(Viewer $viewer, array $input, CarbonImmutable $today): array
    {
        $date = $this->date($input['date']);
        $object = $this->objects->find((int) $input['object_id']) ?? throw ProductionException::invalid('Объект не найден');
        $this->assertCanCreate($viewer, $date, $today, ObjectDirectory::isActive($object->archive));

        $executor = isset($input['brigade_id']) ? Executor::brigade((int) $input['brigade_id']) : Executor::staff((int) $input['executor_staff_id']);
        $this->assertExecutorAllowed($executor, $date);
        $works = $this->works((int) $object->id, $input['works'] ?? []);
        [$fleet, $fleetWarnings] = $this->fleet(null, $input);

        $task = DB::transaction(function () use ($viewer, $input, $date, $object, $executor, $works, $fleet) {
            $task = TimesheetContract::create([
                'team_id' => $this->team($executor, $date)->id,
                'contract_id' => $object->id,
                'creator_staff_id' => $viewer->staffId,
                'text' => $this->text($input['text'] ?? null),
            ] + $fleet);
            $this->replaceWorks($task, $works);
            $this->audit($task->id, 'created', $viewer, null, $this->presenter->snapshot($task));

            return $task;
        });
        $this->syncDrivers([[$task->driver_staff_id, $date]]);

        return [$task->fresh(), array_merge($this->objectWarnings($object, $viewer), $fleetWarnings)];
    }

    /**
     * Скопировать задание на разрешённую дату: переносятся только объект и исполнитель (П-ЗД-03, ИС-ЗД-04; интервью,
     * DM-023)
     *
     * @return array{0: TimesheetContract, 1: array}
     *
     */
    public function copy(Viewer $viewer, int $sourceId, string $date, CarbonImmutable $today): array
    {
        $source = $this->visible($viewer, $sourceId);
        $date = $this->date($date);
        $object = $this->objects->find((int) $source->contract_id) ?? throw ProductionException::invalid('Объект не найден');
        $this->assertCanCreate($viewer, $date, $today, ObjectDirectory::isActive($object->archive));

        $executor = Executor::ofTeam($source->team);
        $this->assertExecutorAllowed($executor, $date);

        $task = DB::transaction(function () use ($viewer, $source, $date, $object, $executor) {
            $task = TimesheetContract::create([
                'team_id' => $this->team($executor, $date)->id,
                'contract_id' => $object->id,
                'creator_staff_id' => $viewer->staffId,
            ]);
            $this->audit($task->id, 'created', $viewer, null, $this->presenter->snapshot($task), ['copied_from' => (int) $source->id]);

            return $task;
        });

        return [$task->fresh(), $this->objectWarnings($object, $viewer)];
    }

    /**
     * Изменить задание (права 2 и 4): содержание, объект, дату, исполнителя, технику и водителя (п. 6.2)
     *
     * @return array{0: TimesheetContract, 1: array}
     *
     */
    public function update(Viewer $viewer, int $taskId, array $input, CarbonImmutable $today): array
    {
        [$task, $warnings, $drivers] = DB::transaction(function () use ($viewer, $taskId, $input, $today) {
            $task = $this->lockVisible($viewer, $taskId);
            $facts = TaskFacts::fromModel($task);
            if (!$this->access->canEdit($viewer, $facts, $today)) {
                throw ProductionException::forbidden('Нет права изменять это задание или его дата вне глубины права');
            }

            $date = isset($input['date']) ? $this->date($input['date']) : $facts->date;
            $dateChanged = $date->toDateString() !== $facts->date->toDateString();
            if ($dateChanged && !$this->access->canEdit($viewer, $facts, $today, $date)) {
                throw ProductionException::invalid('Новая дата вне глубины права на изменение задания', 'date_out_of_depth');
            }

            $object = null;
            $warnings = [];
            if (isset($input['object_id']) && (int) $input['object_id'] !== (int) $task->contract_id) {
                $object = $this->objects->find((int) $input['object_id']) ?? throw ProductionException::invalid('Объект не найден');
                if (!ObjectDirectory::isActive($object->archive)) {
                    throw ProductionException::invalid('Объект в архиве: перенести на него задание нельзя', 'archived_object');
                }
                $warnings = $this->objectWarnings($object, $viewer);
            }
            $contractId = $object ? (int) $object->id : (int) $task->contract_id;

            $current = Executor::ofTeam($task->team);
            $executor = $current;
            if (isset($input['brigade_id'])) {
                $executor = Executor::brigade((int) $input['brigade_id']);
            } elseif (isset($input['executor_staff_id'])) {
                $executor = Executor::staff((int) $input['executor_staff_id']);
            }
            $executorChanged = !$executor->equals($current);
            // Новый исполнитель проверяется полностью; при переносе даты бригада должна существовать и на новую дату
            if ($executorChanged || ($dateChanged && $executor->isBrigade())) {
                $this->assertExecutorAllowed($executor, $date);
            }

            $works = null;
            if (array_key_exists('works', $input)) {
                $works = $this->works($contractId, $input['works'] ?? []);
            } elseif ($object && $task->works()->exists()) {
                // Виды работ задания — только из плана объекта (П-ЗД-11): при смене объекта они должны быть в его плане
                $works = $this->works($contractId, $task->works->map(fn ($w) => ['work_type_id' => $w->work_type_id, 'quantity' => $w->quantity])->all());
            }
            [$fleet, $fleetWarnings] = $this->fleet($task, $input);

            $before = $this->presenter->snapshot($task);
            $oldTeamId = (int) $task->team_id;
            if ($dateChanged || $executorChanged) {
                $task->team_id = $this->team($executor, $date)->id;
            }
            $task->contract_id = $contractId;
            if (array_key_exists('text', $input)) {
                $task->text = $this->text($input['text']);
            }
            $task->fill($fleet);
            $task->save();
            if ($works !== null) {
                $this->replaceWorks($task, $works);
            }
            if ($oldTeamId !== (int) $task->team_id) {
                $this->releaseTeam($oldTeamId);
            }

            $task = $task->fresh();
            $after = $this->presenter->snapshot($task);
            $fields = TaskPresenter::changedFields($before, $after);
            if ($fields) {
                $this->audit($task->id, 'updated', $viewer, $before, $after, ['fields' => $fields]);
            }
            // Смена объекта: в том же окне — готовность прежнего и нового объекта (П-ЗД-29, П-ФК-41)
            if ($object && is_array($input['readiness'] ?? null)) {
                $this->reports->applyObjectChangeReadiness($viewer, $task, (int) $before['object']['id'], $contractId, $input['readiness']);
            }
            $this->reports->afterTaskUpdate($viewer, $task, $before, $after, $today, $executorChanged);
            if ($executorChanged) {
                $this->notifyPreviousExecutor($task, $before, $after, $viewer, $today);
            }
            // Водитель или дата сменились — сообщения водителям «нет заданий» / «вы назначены» (П-СМ-22)
            $drivers = [];
            if (in_array('driver', $fields, true) || in_array('date', $fields, true)) {
                $drivers = [[$before['driver']['staff_id'] ?? null, $this->date($before['date'])], [$after['driver']['staff_id'] ?? null, $this->date($after['date'])]];
            }

            return [$task, array_merge($warnings, $fleetWarnings), $drivers];
        });
        $this->syncDrivers($drivers);

        return [$task, $warnings];
    }

    /**
     * Удалить задание (права 3 и 5) — независимо от отчёта (П-ЗД-27); отчёт удаляется вместе с ним, выбор по
     * готовности объекта — $readinessChoice keep / restore (П-ЗД-28, П-ФК-40)
     */
    public function delete(Viewer $viewer, int $taskId, CarbonImmutable $today, ?string $readinessChoice = null): void
    {
        [$before, $photos] = DB::transaction(function () use ($viewer, $taskId, $today, $readinessChoice) {
            $task = $this->lockVisible($viewer, $taskId);
            if (!$this->access->canDelete($viewer, TaskFacts::fromModel($task), $today)) {
                throw ProductionException::forbidden('Нет права удалять это задание или его дата вне глубины права');
            }

            $before = $this->presenter->snapshot($task);
            $photos = $this->reports->beforeTaskDelete($viewer, $task, $readinessChoice);
            $teamId = (int) $task->team_id;
            $task->delete();
            $this->releaseTeam($teamId);
            $this->audit($taskId, 'deleted', $viewer, $before, null);

            return [$before, $photos];
        });
        $this->reports->afterTaskDelete((int) $before['object']['id'], $photos);
        $this->syncDrivers([[$before['driver']['staff_id'] ?? null, $this->date($before['date'])]]);
    }

    /** @param array<int, array{0: ?int, 1: CarbonImmutable}> $pairs водитель и дата */
    private function syncDrivers(array $pairs): void
    {
        foreach ($pairs as [$driverStaffId, $date]) {
            if ($driverStaffId) {
                $this->attendance->syncDriverNotices((int) $driverStaffId, $date);
            }
        }
    }

    /** Пригласить в задание пользователя приложения (право 7, П-ЗД-24) */
    public function invite(Viewer $viewer, int $taskId, int $staffId): void
    {
        DB::transaction(function () use ($viewer, $taskId, $staffId) {
            $task = $this->lockVisible($viewer, $taskId);
            $this->assertCanInvite($viewer, $task);
            if (!$this->accounts->isStaffIdActive($staffId)) {
                throw ProductionException::invalid('Пригласить можно только пользователя приложения');
            }
            if ($task->activeInvites()->where('staff_id', $staffId)->exists()) {
                return;
            }

            TimesheetContractInvite::create([
                'timesheet_contract_id' => $task->id,
                'staff_id' => $staffId,
                'invited_by_staff_id' => $viewer->staffId,
                'invited_at' => now(),
            ]);
            $this->audit($task->id, 'invited', $viewer, null, ['staff' => $this->person($staffId)]);
        });
    }

    /** Отозвать приглашение: доступ пропадает, если нет другого основания; сообщения остаются (П-ЗД-24, П-ПР-12) */
    public function revokeInvite(Viewer $viewer, int $taskId, int $staffId): void
    {
        DB::transaction(function () use ($viewer, $taskId, $staffId) {
            $task = $this->lockVisible($viewer, $taskId);
            $this->assertCanInvite($viewer, $task);
            $invite = $task->activeInvites()->where('staff_id', $staffId)->first()
                ?? throw ProductionException::notFound('Приглашение не найдено');

            $invite->update(['revoked_at' => now(), 'revoked_by_staff_id' => $viewer->staffId]);
            $this->audit($task->id, 'invite_revoked', $viewer, ['staff' => $this->person($staffId)], null);
        });
    }

    /** Действующие приглашения — для обладателя права 7 (п. 4.4 «Участники задания») */
    public function invites(TimesheetContract $task): array
    {
        return $task->activeInvites()->orderBy('id')->get()->map(fn (TimesheetContractInvite $invite) => [
            'staff' => $this->person((int) $invite->staff_id),
            'invited_by' => $this->person((int) $invite->invited_by_staff_id),
            'invited_at' => $invite->invited_at?->toIso8601String(),
        ])->all();
    }

    private function assertCanCreate(Viewer $viewer, CarbonImmutable $date, CarbonImmutable $today, bool $objectActive): void
    {
        if ($this->access->canCreate($viewer, $date, $today, $objectActive)) {
            return;
        }
        throw match (true) {
            !$viewer->rights->has(Right::TaskCreate) => ProductionException::forbidden('Нет права «Создавать задания»'),
            $date->toDateString() < $today->toDateString() => ProductionException::invalid('Нельзя создать задание на прошедшую дату', 'past_date'),
            default => ProductionException::invalid('Объект в архиве: задание на него создать нельзя', 'archived_object'),
        };
    }

    private function assertCanInvite(Viewer $viewer, TimesheetContract $task): void
    {
        if (!$this->access->canInvite($viewer, TaskFacts::fromModel($task))) {
            throw ProductionException::forbidden('Нет права «Открывать доступ к заданию»');
        }
    }

    /**
     * Исполнитель — бригада из ERP (не расформированная к дате задания) или сотрудник с учётной записью в приложении
     * (П-ЗД-12, П-ОБ-21)
     */
    private function assertExecutorAllowed(Executor $executor, CarbonImmutable $date): void
    {
        if ($executor->isBrigade()) {
            $brigade = Brigade::find($executor->brigadeId) ?? throw ProductionException::invalid('Бригада не найдена');
            if ($brigade->disbanded_on !== null && $brigade->disbanded_on->toDateString() <= $date->toDateString()) {
                throw ProductionException::invalid('Бригада расформирована', 'brigade_disbanded');
            }

            return;
        }
        if (!$this->accounts->isStaffIdActive($executor->staffId)) {
            throw ProductionException::invalid('Исполнителем-сотрудником может быть только сотрудник с учётной записью в приложении', 'executor_without_account');
        }
    }

    /** Запись «исполнитель на дату» план-графика */
    private function team(Executor $executor, CarbonImmutable $date): TimesheetTeam
    {
        $day = $date->toDateString();
        if (!$executor->isBrigade()) {
            return TimesheetTeam::whereNull('brigade_id')->where('staff_id', $executor->staffId)->where('day', $day)->orderBy('id')->first()
                ?? TimesheetTeam::create(['staff_id' => $executor->staffId, 'brigade_id' => null, 'day' => $day]);
        }

        $existing = TimesheetTeam::where('brigade_id', $executor->brigadeId)->where('day', $day)->first();
        if ($existing) {
            return $existing;
        }
        $brigade = Brigade::findOrFail($executor->brigadeId);
        $teamStaff = $brigade->foreman_staff_id
            ?? BrigadeMember::activeOn($date)->where('brigade_id', $brigade->id)->orderBy('joined_on')->orderBy('id')->value('staff_id')
            ?? throw ProductionException::invalid('В бригаде нет ни бригадира, ни состава в ERP', 'brigade_empty');

        try {
            return TimesheetTeam::create(['staff_id' => $teamStaff, 'brigade_id' => $brigade->id, 'day' => $day]);
        } catch (QueryException $error) {
            // Одновременное создание первого задания бригады на дату: запись уже добавил другой запрос
            if ((int) ($error->errorInfo[1] ?? 0) !== 1062) {
                throw $error;
            }

            return TimesheetTeam::where('brigade_id', $brigade->id)->where('day', $day)->firstOrFail();
        }
    }

    /** Запись «исполнитель на дату» без заданий больше не нужна: иначе на сайте осталась бы пустая строка */
    private function releaseTeam(int $teamId): void
    {
        if (!TimesheetContract::where('team_id', $teamId)->exists()) {
            TimesheetTeam::whereKey($teamId)->delete();
        }
    }

    /**
     * Виды работ задания — только из плана объекта, без повторов (П-ЗД-11, П-ОБ-33)
     *
     * @return array<int, array{work_type_id: int, quantity: ?string}>
     *
     */
    private function works(int $contractId, array $works): array
    {
        if (!$works) {
            return [];
        }
        $plan = ContractPlanRevision::currentFor($contractId)
            ?? throw ProductionException::invalid('У объекта нет плана работ', 'no_plan');
        $allowed = $plan->items()->pluck('work_type_id')->map(fn ($id) => (int) $id)->all();

        $result = [];
        foreach ($works as $work) {
            $id = (int) $work['work_type_id'];
            if (!in_array($id, $allowed, true)) {
                throw ProductionException::invalid('Вид работ не входит в план объекта', 'work_not_in_plan');
            }
            if (isset($result[$id])) {
                throw ProductionException::invalid('Вид работ указан дважды');
            }
            $result[$id] = ['work_type_id' => $id, 'quantity' => isset($work['quantity']) ? (string) $work['quantity'] : null];
        }

        return array_values($result);
    }

    private function replaceWorks(TimesheetContract $task, array $works): void
    {
        $task->works()->delete();
        foreach ($works as $work) {
            $task->works()->create($work);
        }
    }

    /**
     * Техника и водитель (П-ЗД-15…17)
     *
     * @return array{0: array, 1: array} поля задания и предупреждения
     *
     */
    private function fleet(?TimesheetContract $task, array $input): array
    {
        $currentVehicle = $task?->vehicle_id !== null ? (int) $task->vehicle_id : null;
        $currentDriver = $task?->driver_id !== null ? (int) $task->driver_id : null;

        $vehicleId = array_key_exists('vehicle_id', $input) ? ($input['vehicle_id'] !== null ? (int) $input['vehicle_id'] : null) : $currentVehicle;
        $vehicleChanged = $vehicleId !== $currentVehicle;
        $driverId = array_key_exists('driver_id', $input)
            ? ($input['driver_id'] !== null ? (int) $input['driver_id'] : null)
            : ($vehicleChanged ? null : $currentDriver);
        $driverChanged = $driverId !== $currentDriver;
        if (!$vehicleChanged && !$driverChanged) {
            return [[], []];
        }

        $noDriver = ['driver_id' => null, 'driver_staff_id' => null, 'driver_name' => null, 'driver_assigned' => null];
        if ($vehicleId === null) {
            if ($driverId !== null) {
                throw ProductionException::invalid('Водитель назначается вместе с техникой');
            }

            return [['vehicle_id' => null, 'vehicle_name' => null, 'vehicle_plate' => null] + $noDriver, []];
        }

        $fields = [];
        if ($vehicleChanged) {
            $vehicle = Vehicle::find($vehicleId);
            if (!$vehicle || $vehicle->not_used) {
                throw ProductionException::invalid('Машина не используется или удалена из справочника ERP', 'vehicle_unavailable');
            }
            $fields = ['vehicle_id' => $vehicle->id, 'vehicle_name' => $vehicle->name, 'vehicle_plate' => $vehicle->plate];
        }
        if ($driverId === null) {
            return [$fields + $noDriver, []];
        }

        $driver = Driver::with('staff')->find($driverId);
        if (!$driver || $driver->not_used || !$driver->staff?->working) {
            throw ProductionException::invalid('Водитель не используется или удалён из списка водителей ERP', 'driver_unavailable');
        }
        $assigned = DB::table('vehicle_drivers')->where('vehicle_id', $vehicleId)->where('driver_id', $driver->id)->exists();
        $fields += [
            'driver_id' => $driver->id,
            'driver_staff_id' => $driver->staff_id,
            'driver_name' => ObjectDirectory::name($driver->staff->sname, $driver->staff->fname, $driver->staff->mname),
            'driver_assigned' => $assigned,
        ];

        return [$fields, $assigned ? [] : [['code' => 'driver_not_assigned', 'message' => 'Водитель не закреплён за этой машиной']]];
    }

    /** Объект чужой или без ответственного — предупреждение, задание создаётся (П-ЗД-08) */
    private function objectWarnings(object $object, Viewer $viewer): array
    {
        $responsible = $object->responsible_staff_id ? (int) $object->responsible_staff_id : null;
        if ($responsible === $viewer->staffId) {
            return [];
        }

        return [[
            'code' => 'foreign_object',
            'message' => $responsible
                ? 'Ответственный за объект — '.ObjectDirectory::name($object->r_sname, $object->r_fname, $object->r_mname)
                : 'У объекта нет ответственного начальника участка',
        ]];
    }

    /**
     * Прежний исполнитель — при бригаде каждый её текущий член — получает уведомление до ручного закрытия; оно
     * остаётся, даже если доступа к заданию больше нет (П-ЗД-32, п. 9.3)
     */
    private function notifyPreviousExecutor(TimesheetContract $task, array $before, array $after, Viewer $viewer, CarbonImmutable $today): void
    {
        $previous = $before['executor'];
        $recipients = $previous['type'] === 'brigade'
            ? collect(Brigade::currentStaff([(int) $previous['brigade_id']], $today)[(int) $previous['brigade_id']] ?? [])
            : collect([(int) $previous['staff_id']]);
        $recipients = $recipients->unique()->reject(fn ($id) => $id === $viewer->staffId);

        $object = $after['object'];
        $body = sprintf('Объект %s «%s», %s. Новый исполнитель: %s. Изменил: %s.',
            $object['number'], $object['title'], CarbonImmutable::parse($after['date'])->format('d.m.Y'),
            $after['executor']['name'], $this->person($viewer->staffId)['name']);

        foreach ($recipients as $staffId) {
            StaffNotification::create([
                'staff_id' => $staffId,
                'kind' => StaffNotification::KIND_TASK_EXECUTOR_CHANGED,
                'title' => 'Задание передано другому исполнителю',
                'body' => $body,
                'task_id' => $task->id,
                'payload' => [
                    'task_id' => (int) $task->id,
                    'date' => $after['date'],
                    'object' => $object,
                    'from' => $previous,
                    'to' => $after['executor'],
                    'by' => $this->person($viewer->staffId),
                ],
            ]);
        }
    }

    private function lockVisible(Viewer $viewer, int $taskId): TimesheetContract
    {
        $task = TimesheetContract::whereKey($taskId)->lockForUpdate()->first();
        if (!$task || !$this->access->canView($viewer, TaskFacts::fromModel($task))) {
            throw ProductionException::notFound();
        }

        return $task;
    }

    private function audit(int $taskId, string $action, Viewer $viewer, ?array $before, ?array $after, array $context = []): void
    {
        AuditEntry::create([
            'entity_type' => AuditEntry::ENTITY_TASK,
            'entity_id' => $taskId,
            'action' => $action,
            'actor_staff_id' => $viewer->staffId,
            'before' => $before,
            'after' => $after,
            'context' => $context ?: null,
        ]);
    }

    private function person(int $staffId): array
    {
        $staff = Staff::query()->select(['id', 'sname', 'fname', 'mname'])->find($staffId);

        return ['id' => $staffId, 'name' => $staff ? ObjectDirectory::name($staff->sname, $staff->fname, $staff->mname) : null];
    }

    private function text(?string $text): ?string
    {
        $text = $text !== null ? trim($text) : null;

        return $text === '' ? null : $text;
    }

    private function date(string $date): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $date, config('production.timezone'));
    }
}
