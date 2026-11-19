<?php

namespace App\Production\Reports;

use App\Models\ContractReadinessEvent;
use App\Models\TaskReport;
use App\Models\TaskReportItem;
use App\Models\TimesheetContract;
use App\Production\Erp\ErpGateway;
use App\Production\Rights\Grant;
use App\Production\Rights\Right;
use App\Production\Tasks\TaskFacts;
use App\Production\Tasks\TaskPresenter;
use App\Production\Tasks\TaskVisibility;
use App\Production\Tasks\Viewer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Списки раздела отчётов: на согласование, история выполнения объекта, задания без отчёта */
final class ReportQueries
{
    private const LIST_LIMIT = 200;

    public function __construct(
        private readonly ReportAccess $access,
        private readonly ReportPresenter $presenter,
        private readonly ReportRecipients $recipients,
        private readonly ErpGateway $erp,
    ) {
    }

    /**
     * На согласование (п. 8.4): отправленные и черновики по заданиям, которые сотрудник создал, и по заданиям в области
     * права 9, которые он видит; сначала отправленные, новые сверху
     *
     * @return Collection<int, TimesheetContract>
     */
    public function pending(Viewer $viewer): Collection
    {
        $grants = $viewer->rights->grantsOf(Right::ReportApproveOthers);
        $all = $grants->contains(fn (Grant $g) => $g->scopeAll);
        $creators = $grants->flatMap(fn (Grant $g) => $g->staffIds)->unique()->values()->all();

        $query = (new TaskVisibility())->query($viewer)
            ->join('task_reports as r', 'r.task_id', '=', 'timesheet_contracts.id')
            ->whereIn('r.status', [TaskReport::SUBMITTED, TaskReport::DRAFT]);
        if (!$all) {
            $query->where(function (Builder $q) use ($viewer, $creators) {
                $q->where('timesheet_contracts.creator_staff_id', $viewer->staffId);
                if ($creators) {
                    $q->orWhereIn('timesheet_contracts.creator_staff_id', $creators);
                }
            });
        }

        return $query->orderByRaw("r.status = 'submitted' DESC")
            ->orderByDesc('r.updated_at')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->filter(fn (TimesheetContract $task) => $this->access->canApprove($viewer, TaskFacts::fromModel($task)))
            ->values();
    }

    /**
     * История выполнения объекта (П-ФК-34): изменения готовности видны всем, кто видит задание; согласованные отчёты —
     * только по заданиям, которые сотрудник видит (раздел 12, п. 23)
     */
    public function objectHistory(Viewer $viewer, int $contractId): array
    {
        $events = ContractReadinessEvent::where('contract_id', $contractId)->orderByDesc('id')->limit(self::LIST_LIMIT)->get()
            ->map(fn (ContractReadinessEvent $e) => [
                'type' => 'readiness',
                'at' => $e->created_at?->toIso8601String(),
                'before' => $e->percent_before !== null ? (float) $e->percent_before : null,
                'after' => $e->percent_after !== null ? (float) $e->percent_after : null,
                'source' => $e->source,
                'source_title' => ContractReadinessEvent::sourceTitle($e->source),
                'reason' => $e->reason,
                'choice' => $e->details['choice'] ?? null,
                'by' => $e->staff_id ? $this->presenter->person((int) $e->staff_id) : ($e->admin_id ? ['id' => null, 'name' => 'ERP'] : null),
            ]);

        $tasks = (new TaskVisibility())->query($viewer)
            ->where('timesheet_contracts.contract_id', $contractId)
            ->join('task_reports as r', 'r.task_id', '=', 'timesheet_contracts.id')
            ->where('r.status', TaskReport::APPROVED)
            ->orderByDesc('t.day')
            ->limit(self::LIST_LIMIT)
            ->with('team')
            ->get();
        $reports = TaskReport::whereIn('task_id', $tasks->pluck('id'))->with('items')->get()->keyBy('task_id');
        $executors = app(TaskPresenter::class)->executors($tasks);
        $types = $this->erp->workTypes($reports->flatMap(fn (TaskReport $r) => $r->items->pluck('work_type_id'))->map(fn ($id) => (int) $id)->unique()->values()->all());
        $this->presenter->loadPeople($reports->flatMap(fn (TaskReport $r) => [$r->submitted_by_staff_id, $r->decided_by_staff_id, $r->edited_by_staff_id])->all());

        $approved = $tasks->map(function (TimesheetContract $task) use ($reports, $executors, $types) {
            $report = $reports->get($task->id);

            return [
                'type' => 'report',
                'at' => $report->decided_at?->toIso8601String(),
                'task_id' => (int) $task->id,
                'date' => substr((string) $task->team->day, 0, 10),
                'executor' => $executors[(int) $task->id],
                'items' => $report->items->sortBy('id')->map(fn (TaskReportItem $i) => [
                    'name' => $types[(int) $i->work_type_id]['name'] ?? null,
                    'unit' => $types[(int) $i->work_type_id]['unit'] ?? null,
                    'declared' => $i->quantity_declared,
                    'approved' => $i->quantity_approved,
                    'edited' => $i->edited_at !== null,
                ])->values()->all(),
                'submitted_by' => $this->presenter->person($report->submitted_by_staff_id),
                'decided_by' => $this->presenter->person($report->decided_by_staff_id),
                'edited_by' => $this->presenter->person($report->edited_by_staff_id),
                'edited_at' => $report->edited_at?->toIso8601String(),
            ];
        });

        return $events->concat($approved)->sortByDesc('at')->values()->all();
    }

    /**
     * Задания без предоставленного отчёта, по которым сотрудник получает напоминание и плашку (П-ЗД-37): дата прошла,
     * он — текущий член бригады-исполнителя или исполнитель с правом 8; при черновике или отклонённом — только автор
     *
     * @return Collection<int, TimesheetContract>
     */
    public function due(Viewer $viewer, CarbonImmutable $today): Collection
    {
        if (!$viewer->rights->has(Right::ReportSubmit)) {
            return collect();
        }

        return $this->dueQuery($today)
            ->where(function (Builder $q) use ($viewer) {
                $q->where(fn ($w) => $w->whereNull('t.brigade_id')->where('t.staff_id', $viewer->staffId));
                if ($viewer->brigadeIds) {
                    $q->orWhereIn('t.brigade_id', $viewer->brigadeIds);
                }
            })
            ->orderBy('t.day')
            ->limit(self::LIST_LIMIT)
            ->get()
            // Есть черновик или отклонённый — только его автор, если он ещё вправе его править
            ->filter(fn (TimesheetContract $task) => !$task->report
                || ((int) $task->report->author_staff_id === $viewer->staffId && $task->report->author_locked_at === null))
            ->values();
    }

    /**
     * Все задания без предоставленного отчёта — для ежедневного напоминания в 09:00. Задания прототипа (без создателя)
     * не учитываются; с какой даты — настройка production.report_reminders_from (чтобы при выкладке не пришли
     * напоминания за всю историю)
     *
     * @return Builder<TimesheetContract>
     */
    public function dueQuery(CarbonImmutable $today): Builder
    {
        $query = TimesheetContract::query()
            ->select('timesheet_contracts.*')
            ->join('timesheet_teams as t', 't.id', '=', 'timesheet_contracts.team_id')
            ->leftJoin('task_reports as r', 'r.task_id', '=', 'timesheet_contracts.id')
            ->whereNotNull('timesheet_contracts.creator_staff_id')
            ->where('t.day', '<', $today->toDateString())
            ->where(fn ($q) => $q->whereNull('r.id')->orWhereIn('r.status', [TaskReport::DRAFT, TaskReport::REJECTED]))
            ->with(['report', 'team']);
        if ($from = config('production.report_reminders_from')) {
            $query->where('t.day', '>=', $from);
        }

        return $query;
    }
}
