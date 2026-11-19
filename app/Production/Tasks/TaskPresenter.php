<?php

namespace App\Production\Tasks;

use App\Models\AuditEntry;
use App\Models\TimesheetContract;
use App\Production\Attendance\AttendanceService;
use App\Production\Objects\ObjectDirectory;
use App\Production\Reports\ReportPresenter;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/** Задание для приложения и его снимок для журнала */
final class TaskPresenter
{
    public const RELATIONS = ['team.brigade.foreman', 'team.profile', 'creator', 'works.workType', 'activeInvites'];

    public function __construct(
        private readonly TaskAccess $access,
        private readonly ObjectDirectory $objects,
        private readonly AttendanceService $attendance,
        private readonly ReportPresenter $reports,
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
        $objects = $this->objects->findMany($tasks->pluck('contract_id')->map(fn ($id) => (int) $id)->unique()->values()->all());
        $badges = $this->attendance->badges($viewer, $tasks);
        $reports = $this->reports->marks($tasks, $today->toDateString());

        return $tasks->map(fn (TimesheetContract $task) => $this->item($task, $viewer, $today, $objects) + [
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
        $task->loadMissing(['team.brigade', 'team.profile', 'works.workType']);
        $object = $this->objects->find((int) $task->contract_id);

        return [
            'date' => $this->day($task),
            'object' => ['id' => (int) $task->contract_id, 'number' => $object?->object_number, 'title' => $object?->title],
            'executor' => $this->executor($task),
            'text' => $task->text,
            'works' => $this->works($task),
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
        return AuditEntry::query()
            ->where('entity_type', AuditEntry::ENTITY_TASK)
            ->where('entity_id', $taskId)
            ->with('actor:id,sname,fname,mname')
            ->orderBy('id')
            ->get()
            ->map(fn (AuditEntry $entry) => [
                'id' => $entry->id,
                'at' => $entry->created_at?->toIso8601String(),
                'action' => $entry->action,
                'actor' => $entry->actor ? ['id' => $entry->actor->id, 'name' => ObjectDirectory::name($entry->actor->sname, $entry->actor->fname, $entry->actor->mname)] : null,
                'fields' => $entry->context['fields'] ?? null,
                'before' => $entry->before,
                'after' => $entry->after,
                'context' => $entry->context,
            ])
            ->all();
    }

    public function executor(TimesheetContract $task): array
    {
        $team = $task->team;
        if ($team->brigade_id) {
            $brigade = $team->brigade;

            return [
                'type' => 'brigade',
                'brigade_id' => (int) $team->brigade_id,
                'name' => $brigade?->name,
                'foreman' => $brigade?->foreman ? ['id' => $brigade->foreman->id, 'name' => ObjectDirectory::name($brigade->foreman->sname, $brigade->foreman->fname, $brigade->foreman->mname)] : null,
            ];
        }
        $profile = $team->profile;

        return [
            'type' => 'staff',
            'staff_id' => (int) $team->staff_id,
            'name' => $profile ? ObjectDirectory::name($profile->sname, $profile->fname, $profile->mname) : null,
        ];
    }

    private function item(TimesheetContract $task, Viewer $viewer, CarbonInterface $today, Collection $objects): array
    {
        $object = $objects->get($task->contract_id);
        $facts = TaskFacts::fromModel($task, $object ? ObjectDirectory::isActive($object->archive) : false);
        $creator = $task->creator;

        return [
            'id' => (int) $task->id,
            'date' => $this->day($task),
            'object' => $object ? $this->objects->item($object, $viewer->staffId, null) : ['id' => (int) $task->contract_id],
            'executor' => $this->executor($task),
            'creator' => $creator ? ['id' => $creator->id, 'name' => ObjectDirectory::name($creator->sname, $creator->fname, $creator->mname)] : null,
            'text' => $task->text,
            'works' => $this->works($task),
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

    private function works(TimesheetContract $task): array
    {
        return $task->works->sortBy('id')->map(fn ($work) => [
            'work_type_id' => (int) $work->work_type_id,
            'name' => $work->workType?->name,
            'unit' => $work->workType?->unit,
            'quantity' => $work->quantity !== null ? (float) $work->quantity : null,
        ])->values()->all();
    }

    private function day(TimesheetContract $task): string
    {
        return substr((string) $task->team->day, 0, 10);
    }
}
