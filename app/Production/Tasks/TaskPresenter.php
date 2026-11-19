<?php

namespace App\Production\Tasks;

use App\Models\AuditEntry;
use App\Models\TimesheetContract;
use App\Production\Attendance\AttendanceService;
use App\Production\Erp\ErpGateway;
use App\Production\Objects\ObjectDirectory;
use App\Production\Reports\ReportPresenter;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/** Задание для приложения и его снимок для журнала */
final class TaskPresenter
{
    /** Связи заданий в таблицах приложения; данные ERP (бригады, люди, виды работ, объекты) — из шлюза ERP */
    public const RELATIONS = ['team', 'works', 'activeInvites'];

    public function __construct(
        private readonly TaskAccess $access,
        private readonly ObjectDirectory $objects,
        private readonly AttendanceService $attendance,
        private readonly ReportPresenter $reports,
        private readonly ErpGateway $erp,
    ) {
    }

    /**
     * Задания для календаря с признаками доступа текущего сотрудника.
     *
     * @param Collection<int, TimesheetContract> $tasks
     */
    public function list(Collection $tasks, Viewer $viewer, CarbonInterface $today): array
    {
        $tasks = (new EloquentCollection($tasks->all()))->loadMissing(self::RELATIONS)->loadCount('chat');
        $erp = $this->erpData($tasks, $viewer->staffId);
        $badges = $this->attendance->badges($viewer, $tasks);
        $reports = $this->reports->marks($tasks, $today->toDateString());

        return $tasks->map(fn (TimesheetContract $task) => $this->item($task, $viewer, $today, $erp) + [
            // Значок неподтверждённых выходов (П-ЗД-44, П-СМ-19)
            'attendance_unconfirmed' => $badges[(int) $task->id] ?? false,
            // Отметка отчёта: нет / черновик / отправлен / согласован / отклонён и «нет отчёта» (FR9-026, П-ЗД-37)
            'report' => $reports[(int) $task->id],
        ])->values()->all();
    }

    public function one(TimesheetContract $task, Viewer $viewer, CarbonInterface $today): array
    {
        return $this->list(collect([$task]), $viewer, $today)[0];
    }

    /** Поля задания, которые пишутся в журнал «было / стало» (п. 6.6, п. 9.4) */
    public function snapshot(TimesheetContract $task): array
    {
        $task->loadMissing(['team', 'works']);
        $erp = $this->erpData(collect([$task]), null);
        $object = $erp['objects'][(int) $task->contract_id] ?? null;

        return [
            'date' => $this->day($task),
            'object' => ['id' => (int) $task->contract_id, 'number' => $object['number'] ?? null, 'title' => $object['title'] ?? null],
            'executor' => $this->executorOf($task, $erp),
            'text' => $task->text,
            'works' => $this->works($task, $erp),
            'vehicle' => $this->vehicle($task),
            'driver' => $this->driver($task),
        ];
    }

    /** Какие поля изменились между снимками */
    public static function changedFields(array $before, array $after): array
    {
        return array_values(array_filter(array_keys($after), fn ($key) => ($before[$key] ?? null) != ($after[$key] ?? null)));
    }

    /** История изменений задания для карточки (ИС-ЗД-09, ИС-ЗД-14: приглашения, исполнители) */
    public function history(int $taskId): array
    {
        $entries = AuditEntry::query()
            ->where('entity_type', AuditEntry::ENTITY_TASK)
            ->where('entity_id', $taskId)
            ->orderBy('id')
            ->get();
        $people = $this->erp->people($entries->pluck('actor_staff_id')->filter()->unique()->values()->all());

        return $entries->map(fn (AuditEntry $entry) => [
            'id' => $entry->id,
            'at' => $entry->created_at?->toIso8601String(),
            'action' => $entry->action,
            'actor' => $entry->actor_staff_id && isset($people[(int) $entry->actor_staff_id])
                ? ['id' => (int) $entry->actor_staff_id, 'name' => $people[(int) $entry->actor_staff_id]['name']]
                : null,
            'fields' => $entry->context['fields'] ?? null,
            'before' => $entry->before,
            'after' => $entry->after,
            'context' => $entry->context,
        ])->all();
    }

    public function executor(TimesheetContract $task): array
    {
        return $this->executors(collect([$task]))[(int) $task->id];
    }

    /**
     * Исполнители заданий одним набором запросов к ERP
     *
     * @param Collection<int, TimesheetContract> $tasks
     * @return array<int, array> task_id => исполнитель
     */
    public function executors(Collection $tasks): array
    {
        $tasks = (new EloquentCollection($tasks->all()))->loadMissing('team');
        $erp = $this->erpData($tasks, null);

        return $tasks->mapWithKeys(fn (TimesheetContract $task) => [(int) $task->id => $this->executorOf($task, $erp)])->all();
    }

    /**
     * Данные ERP для заданий одним набором запросов: объекты, бригады, люди (бригадиры, исполнители, создатели),
     * виды работ
     *
     * @param Collection<int, TimesheetContract> $tasks
     * @return array{objects: array<int, array>, presented: array<int, array>, brigades: array<int, array>, people: array<int, array>, workTypes: array<int, array>}
     */
    private function erpData(Collection $tasks, ?int $staffId): array
    {
        $objects = $this->erp->objects($tasks->pluck('contract_id')->map(fn ($id) => (int) $id)->unique()->values()->all());
        $brigades = $this->erp->brigades($tasks->pluck('team.brigade_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all());
        $peopleIds = collect($brigades)->pluck('foreman_staff_id')
            ->merge($tasks->filter(fn ($task) => !$task->team->brigade_id)->pluck('team.staff_id'))
            ->merge($tasks->pluck('creator_staff_id'))
            ->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $workTypeIds = $tasks->flatMap(fn ($task) => $task->works->pluck('work_type_id'))->map(fn ($id) => (int) $id)->unique()->values()->all();

        return [
            'objects' => $objects,
            'presented' => $staffId !== null ? $this->objects->present($objects, $staffId) : [],
            'brigades' => $brigades,
            'people' => $this->erp->people($peopleIds),
            'workTypes' => $this->erp->workTypes($workTypeIds),
        ];
    }

    private function executorOf(TimesheetContract $task, array $erp): array
    {
        $team = $task->team;
        if ($team->brigade_id) {
            $brigade = $erp['brigades'][(int) $team->brigade_id] ?? null;
            $foreman = $brigade && $brigade['foreman_staff_id'] ? ($erp['people'][$brigade['foreman_staff_id']] ?? null) : null;

            return [
                'type' => 'brigade',
                'brigade_id' => (int) $team->brigade_id,
                'name' => $brigade['name'] ?? null,
                'foreman' => $foreman ? ['id' => $foreman['id'], 'name' => $foreman['name']] : null,
            ];
        }

        return [
            'type' => 'staff',
            'staff_id' => (int) $team->staff_id,
            'name' => $erp['people'][(int) $team->staff_id]['name'] ?? null,
        ];
    }

    private function item(TimesheetContract $task, Viewer $viewer, CarbonInterface $today, array $erp): array
    {
        $object = $erp['objects'][(int) $task->contract_id] ?? null;
        $facts = TaskFacts::fromModel($task, $object ? $object['active'] : false);
        $creator = $task->creator_staff_id ? ($erp['people'][(int) $task->creator_staff_id] ?? null) : null;

        return [
            'id' => (int) $task->id,
            'date' => $this->day($task),
            'object' => $erp['presented'][(int) $task->contract_id] ?? ['id' => (int) $task->contract_id],
            'executor' => $this->executorOf($task, $erp),
            'creator' => $creator ? ['id' => $creator['id'], 'name' => $creator['name']] : null,
            'text' => $task->text,
            'works' => $this->works($task, $erp),
            'vehicle' => $this->vehicle($task),
            'driver' => $this->driver($task),
            'abilities' => $this->access->abilities($viewer, $facts, $today),
            // Значок переписки на карточке, как в прежнем «Табеле»
            'comments_count' => (int) ($task->chat_count ?? 0),
            'updated_at' => $task->updated_at?->toIso8601String(),
        ];
    }

    /** Техника — снимок на момент назначения: остаётся, даже если машину удалили из ERP (П-ОБ-23б) */
    private function vehicle(TimesheetContract $task): ?array
    {
        if ($task->vehicle_id === null && $task->vehicle_name === null) {
            return null;
        }

        return ['id' => $task->vehicle_id ? (int) $task->vehicle_id : null, 'name' => $task->vehicle_name, 'plate' => $task->vehicle_plate];
    }

    /** Водитель — снимок; `assigned` = закреплён за машиной (иначе было предупреждение, П-ЗД-16) */
    private function driver(TimesheetContract $task): ?array
    {
        if ($task->driver_staff_id === null) {
            return null;
        }

        return [
            'id' => $task->driver_id ? (int) $task->driver_id : null,
            'staff_id' => (int) $task->driver_staff_id,
            'name' => $task->driver_name,
            'assigned' => $task->driver_assigned === null ? null : (bool) $task->driver_assigned,
        ];
    }

    private function works(TimesheetContract $task, array $erp): array
    {
        return $task->works->sortBy('id')->map(fn ($work) => [
            'work_type_id' => (int) $work->work_type_id,
            'name' => $erp['workTypes'][(int) $work->work_type_id]['name'] ?? null,
            'unit' => $erp['workTypes'][(int) $work->work_type_id]['unit'] ?? null,
            'quantity' => $work->quantity !== null ? (float) $work->quantity : null,
        ])->values()->all();
    }

    private function day(TimesheetContract $task): string
    {
        return substr((string) $task->team->day, 0, 10);
    }
}
