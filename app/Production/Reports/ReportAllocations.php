<?php

namespace App\Production\Reports;

use App\Models\Erp\ContractPlanRevision;
use App\Models\TaskReport;
use App\Models\TaskReportItem;
use Illuminate\Support\Facades\DB;

/**
 * Накопительный факт объекта и отнесение к допам (П-ФК-22…25, п. 11.2): согласованные количества по датам заданий
 * сначала заполняют проект, превышение — доп у того, кто его выполнил; при одной дате доп получает отчёт,
 * согласованный позже. Отнесение только хранится и пересчитывается при изменении плана, отчётов и заданий.
 */
final class ReportAllocations
{
    /**
     * Согласованные количества по видам работ объекта, без отчёта $exceptReportId
     *
     * @return array<int, float> work_type_id → сумма
     */
    public function approvedTotals(int $contractId, ?int $exceptReportId = null): array
    {
        return $this->approvedItems($contractId)
            ->when($exceptReportId, fn ($q) => $q->where('r.id', '<>', $exceptReportId))
            ->groupBy('i.work_type_id')
            ->selectRaw('i.work_type_id, SUM(i.quantity_approved) as total')
            ->pluck('total', 'work_type_id')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /** Пересчитать отнесение всех согласованных позиций объекта по актуальной редакции плана (П-ФК-24) */
    public function recompute(int $contractId): void
    {
        $plan = ContractPlanRevision::currentFor($contractId);
        $planned = $plan
            ? $plan->items()->pluck('quantity', 'work_type_id')->map(fn ($q) => (float) $q)->all()
            : null;

        $rows = $this->approvedItems($contractId)
            ->select(['i.id', 'i.work_type_id', 'i.quantity_approved', 'i.plan_revision_id', 'i.quantity_in_plan', 'i.quantity_extra', 'i.extra_kind'])
            ->orderBy('t.day')
            ->orderBy('r.decided_at')
            ->orderBy('r.id')
            ->get();

        $left = $planned ?? [];
        foreach ($rows as $row) {
            $quantity = (float) $row->quantity_approved;
            if ($planned === null) {
                // Плана нет — к допам не относится до появления плана (П-ФК-04)
                $next = [null, null, null, null];
            } elseif (!array_key_exists((int) $row->work_type_id, $planned)) {
                $next = [$plan->id, 0.0, $quantity, $quantity > 0 ? TaskReportItem::EXTRA_OFF_PLAN : null];
            } else {
                $rest = max(0.0, $left[(int) $row->work_type_id]);
                $inPlan = min($quantity, $rest);
                $left[(int) $row->work_type_id] = round($rest - $inPlan, 3);
                $extra = round($quantity - $inPlan, 3);
                $next = [$plan->id, round($inPlan, 3), $extra, $extra > 0 ? TaskReportItem::EXTRA_OVERAGE : null];
            }
            $current = [
                $row->plan_revision_id !== null ? (int) $row->plan_revision_id : null,
                $row->quantity_in_plan !== null ? (float) $row->quantity_in_plan : null,
                $row->quantity_extra !== null ? (float) $row->quantity_extra : null,
                $row->extra_kind,
            ];
            if ($current != $next) {
                TaskReportItem::whereKey($row->id)->update([
                    'plan_revision_id' => $next[0],
                    'quantity_in_plan' => $next[1],
                    'quantity_extra' => $next[2],
                    'extra_kind' => $next[3],
                ]);
            }
        }
    }

    /** Позиции, выбывшие из итогов (отчёт отклонён, правка вернула в работу): отнесения у них нет */
    public function clear(int $reportId): void
    {
        TaskReportItem::where('report_id', $reportId)->update(['plan_revision_id' => null, 'quantity_in_plan' => null, 'quantity_extra' => null, 'extra_kind' => null]);
    }

    private function approvedItems(int $contractId)
    {
        return DB::table('task_report_items as i')
            ->join('task_reports as r', 'r.id', '=', 'i.report_id')
            ->join('timesheet_contracts as tc', 'tc.id', '=', 'r.task_id')
            ->join('timesheet_teams as t', 't.id', '=', 'tc.team_id')
            ->where('tc.contract_id', $contractId)
            ->where('r.status', TaskReport::APPROVED)
            ->whereNotNull('i.quantity_approved');
    }
}
