<?php

namespace App\Production\Reports;

use App\Models\TaskReport;
use App\Models\TimesheetContract;
use App\Production\Erp\ErpGateway;
use App\Production\Rights\Right;
use App\Production\Rights\StaffRights;
use Carbon\CarbonImmutable;

/**
 * Кому напоминать об отчёте (П-ЗД-37, раздел 12, п. 6): текущие члены бригады-исполнителя или исполнитель-сотрудник с
 * правом «Заявлять отчёт»; если по заданию уже есть черновик или отклонённый отчёт — только его автор
 */
final class ReportRecipients
{
    public function __construct(private readonly ErpGateway $erp)
    {
    }

    /**
     * @param array<int, bool>|null $memo права сотрудников между вызовами (ежедневная рассылка); без него — свежие
     * @return int[]
     */
    public function forTask(TimesheetContract $task, CarbonImmutable $today, ?TaskReport $report = null, ?array &$memo = null): array
    {
        $memo ??= [];
        $canSubmit = function (int $id) use (&$memo): bool {
            return $memo[$id] ??= StaffRights::load($id)->has(Right::ReportSubmit);
        };
        if ($report && $report->open()) {
            $author = (int) $report->author_staff_id;

            return $report->author_locked_at === null && $canSubmit($author) ? [$author] : [];
        }
        $task->loadMissing('team');
        $staff = $task->team->brigade_id
            ? $this->brigadeStaff((int) $task->team->brigade_id, $today)
            : [(int) $task->team->staff_id];

        return array_values(array_filter($staff, $canSubmit));
    }

    /** @return int[] */
    public function brigadeStaff(int $brigadeId, CarbonImmutable $today): array
    {
        return $this->erp->brigadeStaffOn([$brigadeId], $today)[$brigadeId] ?? [];
    }
}
