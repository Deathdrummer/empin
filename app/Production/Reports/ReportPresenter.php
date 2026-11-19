<?php

namespace App\Production\Reports;

use App\Models\AttendanceMark;
use App\Models\AuditEntry;
use App\Models\TaskReport;
use App\Models\TaskReportItem;
use App\Models\TaskReportPhoto;
use App\Models\TimesheetContract;
use App\Production\Erp\ErpGateway;
use App\Production\Tasks\TaskFacts;
use App\Production\Tasks\Viewer;
use Illuminate\Support\Collection;

/** Отчёт для приложения (п. 8.4): шапка из задания, позиции с планом и согласованным по объекту, фото, история */
final class ReportPresenter
{
    /** @var array<int, ?array> */
    private array $people = [];

    public function __construct(
        private readonly ReportAccess $access,
        private readonly ReportAllocations $allocations,
        private readonly ObjectReadiness $readiness,
        private readonly ErpGateway $erp,
    ) {
    }

    /** Экран отчёта: сам отчёт (или null), данные для заполнения, права */
    public function screen(TimesheetContract $task, ?TaskReport $report, Viewer $viewer): array
    {
        $facts = TaskFacts::fromModel($task);
        $contractId = (int) $task->contract_id;
        $plan = $this->erp->currentPlan($contractId);
        $planTypes = $plan ? $this->erp->workTypes(array_keys($plan['items'])) : [];

        return [
            'report' => $report ? $this->report($report) : null,
            'context' => [
                'object_id' => $contractId,
                'readiness' => $this->readiness->current($contractId),
                // План объекта и уже согласованное по объекту без этого отчёта (П-ФК-20, П-ФК-25, ИС-ФК-10)
                'plan' => $plan ? collect($plan['items'])->map(fn (float $quantity, int $typeId) => [
                    'work_type_id' => $typeId,
                    'name' => $planTypes[$typeId]['name'] ?? null,
                    'unit' => $planTypes[$typeId]['unit'] ?? null,
                    'quantity' => $quantity,
                ])->sortBy('name')->values()->all() : null,
                'approved' => (object) $this->allocations->approvedTotals($contractId, $report?->id),
                'warnings' => $this->warnings($task),
            ],
            'abilities' => $this->access->abilities($viewer, $facts, $report),
        ];
    }

    public function report(TaskReport $report): array
    {
        $report->loadMissing(['items']);
        $photos = TaskReportPhoto::where('report_id', $report->id)->orderBy('id')->get();
        $types = $this->erp->workTypes($report->items->pluck('work_type_id')->map(fn ($id) => (int) $id)->all());
        // Все люди отчёта — одним запросом
        $this->loadPeople(array_merge(
            [$report->author_staff_id, $report->submitted_by_staff_id, $report->decided_by_staff_id, $report->edited_by_staff_id],
            $report->items->pluck('edited_by_staff_id')->all(),
            $photos->pluck('added_by_staff_id')->all(),
        ));

        return [
            'id' => (int) $report->id,
            'task_id' => (int) $report->task_id,
            'status' => $report->status,
            'revision' => (int) $report->revision,
            'author' => $this->person($report->author_staff_id),
            'submitted_by' => $this->person($report->submitted_by_staff_id),
            'submitted_at' => $report->submitted_at?->toIso8601String(),
            'decided_by' => $this->person($report->decided_by_staff_id),
            'decided_at' => $report->decided_at?->toIso8601String(),
            'reject_comment' => $report->reject_comment,
            'edited_by' => $this->person($report->edited_by_staff_id),
            'edited_at' => $report->edited_at?->toIso8601String(),
            'author_locked' => $report->author_locked_at !== null,
            'created_at' => $report->created_at?->toIso8601String(),
            'text' => $report->text,
            'task_percent' => ['declared' => $report->task_percent_declared, 'approved' => $report->task_percent_approved],
            'object_percent' => [
                'prefilled' => $report->object_percent_prefilled,
                'declared' => $report->object_percent_declared,
                'approved' => $report->object_percent_approved,
            ],
            'items' => $report->items->sortBy('id')->map(fn (TaskReportItem $i) => [
                'work_type_id' => (int) $i->work_type_id,
                'name' => $types[(int) $i->work_type_id]['name'] ?? null,
                'unit' => $types[(int) $i->work_type_id]['unit'] ?? null,
                'deleted' => (bool) ($types[(int) $i->work_type_id]['deleted'] ?? false),
                'declared' => $i->quantity_declared,
                'approved' => $i->quantity_approved,
                'edited_by' => $this->person($i->edited_by_staff_id),
                'edited_at' => $i->edited_at?->toIso8601String(),
            ])->values()->all(),
            'photos' => $photos->map(fn (TaskReportPhoto $p) => [
                'id' => (int) $p->id,
                'url' => $p->path,
                'width' => $p->width,
                'height' => $p->height,
                'added_by' => $this->person($p->added_by_staff_id),
                'created_at' => $p->created_at?->toIso8601String(),
                'client_uuid' => $p->client_uuid,
            ])->all(),
            'history' => $this->history($report->id),
        ];
    }

    /**
     * Отметка отчёта в плане-графике (FR9-026) и «нет отчёта» у прошедшего задания (П-ЗД-37)
     *
     * @param Collection<int, TimesheetContract> $tasks
     * @return array<int, array{status: ?string, missing: bool}>
     */
    public function marks(Collection $tasks, string $today): array
    {
        $reports = TaskReport::whereIn('task_id', $tasks->pluck('id'))->get(['task_id', 'status'])->keyBy('task_id');
        // Те же задания, что получают напоминание (ReportQueries::dueQuery)
        $from = (string) config('production.report_reminders_from');
        $result = [];
        foreach ($tasks as $task) {
            $status = $reports->get($task->id)?->status;
            $day = substr((string) $task->team->day, 0, 10);
            $result[(int) $task->id] = [
                'status' => $status,
                'missing' => $task->creator_staff_id !== null
                    && $day < $today
                    && ($from === '' || $day >= $from)
                    && !in_array($status, [TaskReport::SUBMITTED, TaskReport::APPROVED], true),
            ];
        }

        return $result;
    }

    /**
     * Шкалы карточки задания (п. 8.4): процент задания (утверждённый или предварительный, без плана — 0 %, П-ФК-31),
     * выполнение позиций задания в количествах, готовность объекта
     */
    public function progress(TimesheetContract $task, ?TaskReport $report, Viewer $viewer): array
    {
        $task->loadMissing('works');
        $approved = $report?->status === TaskReport::APPROVED;
        $items = $report ? $report->items()->get()->keyBy(fn ($i) => (int) $i->work_type_id) : collect();
        $taskPercent = $approved ? $report->task_percent_approved : $report?->task_percent_declared;

        return [
            'task_percent' => $taskPercent ?? 0.0,
            'task_percent_approved' => $approved && $report->task_percent_approved !== null,
            'works' => $task->works->filter(fn ($w) => $w->quantity !== null)->map(fn ($w) => [
                'work_type_id' => (int) $w->work_type_id,
                'quantity' => (float) $w->quantity,
                'done' => ($item = $items->get((int) $w->work_type_id)) ? ($approved ? $item->quantity_approved : $item->quantity_declared) : null,
                'approved' => $approved,
            ])->values()->all(),
            'readiness' => $this->readiness->current((int) $task->contract_id),
            // Предварительная готовность объекта из несогласованного отчёта (П-ФК-30)
            'readiness_declared' => $report && $report->open() && $report->object_percent_declared !== $report->object_percent_prefilled
                ? $report->object_percent_declared : null,
            'readiness_edit' => $this->access->canEditReadiness($viewer, TaskFacts::fromModel($task)),
        ];
    }

    /** История отчёта: кто, что, когда (п. 8.6) */
    public function history(int $reportId): array
    {
        return AuditEntry::query()
            ->where('entity_type', AuditEntry::ENTITY_REPORT)
            ->where('entity_id', $reportId)
            ->orderBy('id')
            ->get()
            ->map(fn (AuditEntry $e) => [
                'id' => $e->id,
                'at' => $e->created_at?->toIso8601String(),
                'action' => $e->action,
                'actor' => $this->person($e->actor_staff_id),
                'before' => $e->before,
                'after' => $e->after,
                'context' => $e->context,
            ])->all();
    }

    /**
     * Водитель задания не отметил выход — предупреждение согласующему; любая отметка за день, в том числе «в
     * бригаде», его снимает (П-ФК-18, раздел 12, п. 15)
     */
    private function warnings(TimesheetContract $task): array
    {
        if (!$task->driver_staff_id) {
            return [];
        }
        $task->loadMissing('team');
        $marked = AttendanceMark::where('staff_id', $task->driver_staff_id)->whereDate('work_date', substr((string) $task->team->day, 0, 10))->exists();

        return $marked ? [] : [['code' => 'driver_not_marked', 'message' => 'Водитель не отметил выход на смену']];
    }

    public function person(?int $staffId): ?array
    {
        if (!$staffId) {
            return null;
        }
        $this->loadPeople([$staffId]);

        return $this->people[$staffId];
    }

    /** Люди из ERP одним запросом на список; уже загруженные не запрашиваются */
    public function loadPeople(array $staffIds): void
    {
        $missing = array_values(array_diff(array_unique(array_map('intval', array_filter($staffIds))), array_keys($this->people)));
        if (!$missing) {
            return;
        }
        $found = $this->erp->people($missing);
        foreach ($missing as $id) {
            $this->people[$id] = isset($found[$id]) ? ['id' => $id, 'name' => $found[$id]['name']] : null;
        }
    }
}
