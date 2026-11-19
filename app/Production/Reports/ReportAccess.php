<?php

namespace App\Production\Reports;

use App\Models\TaskReport;
use App\Production\Rights\Grant;
use App\Production\Rights\Right;
use App\Production\Tasks\TaskAccess;
use App\Production\Tasks\TaskFacts;
use App\Production\Tasks\Viewer;

/**
 * Кто что может с отчётом (п. 8.2, П-ПР-13…17, П-ФК-10…13, П-ФК-27, П-ЗД-33, П-ЗД-34). Отчёт видит тот, кто видит
 * задание; все права здесь действуют только в видимых заданиях (раздел 12, п. 29)
 */
final class ReportAccess
{
    public function __construct(private readonly TaskAccess $tasks)
    {
    }

    /** Создать отчёт по видимому заданию — право 8, дата задания значения не имеет (П-ФК-06) */
    public function canCreate(Viewer $viewer, TaskFacts $task, ?TaskReport $report): bool
    {
        return !$report && $viewer->rights->has(Right::ReportSubmit) && $this->tasks->canView($viewer, $task);
    }

    /**
     * Править несогласованный отчёт может только его первоначальный создатель с правом 8 (П-ФК-10, П-ПР-14); после
     * смены исполнителя прежний исполнитель это право теряет (П-ЗД-33)
     */
    public function canEdit(Viewer $viewer, TaskFacts $task, ?TaskReport $report): bool
    {
        return $report !== null
            && $report->open()
            && (int) $report->author_staff_id === $viewer->staffId
            && $report->author_locked_at === null
            && $viewer->rights->has(Right::ReportSubmit)
            && $this->tasks->canView($viewer, $task);
    }

    /**
     * Согласующий: создатель задания без отдельного права или владелец права 9 в пределах области по создателям
     * (П-ФК-12а, П-ПР-16)
     */
    public function canApprove(Viewer $viewer, TaskFacts $task, ?TaskReport $report = null): bool
    {
        if ($report !== null && !$report->open()) {
            return false;
        }
        if (!$this->tasks->canView($viewer, $task)) {
            return false;
        }
        if ($task->creatorId !== null && $task->creatorId === $viewer->staffId) {
            return true;
        }

        return $viewer->rights->any(Right::ReportApproveOthers, fn (Grant $g) => $task->creatorId === null ? $g->scopeAll : $g->coversCreator($task->creatorId));
    }

    /** Отклонить можно только отправленный отчёт */
    public function canReject(Viewer $viewer, TaskFacts $task, ?TaskReport $report): bool
    {
        return $report?->status === TaskReport::SUBMITTED && $this->canApprove($viewer, $task, $report);
    }

    /** Правка согласованного — право 10, без ограничения дат (П-ПР-15, П-ФК-27) */
    public function canCorrect(Viewer $viewer, TaskFacts $task, ?TaskReport $report): bool
    {
        return $report?->status === TaskReport::APPROVED
            && $viewer->rights->has(Right::ReportEditApproved)
            && $this->tasks->canView($viewer, $task);
    }

    /** Прямое изменение готовности объекта — право 15 из видимого задания (П-ФК-34, ИС-ФК-11) */
    public function canEditReadiness(Viewer $viewer, TaskFacts $task): bool
    {
        return $viewer->rights->has(Right::ReadinessEdit) && $this->tasks->canView($viewer, $task);
    }

    /** Признаки для приложения; окончательная проверка — на сервере */
    public function abilities(Viewer $viewer, TaskFacts $task, ?TaskReport $report): array
    {
        return [
            'create' => $this->canCreate($viewer, $task, $report),
            'edit' => $this->canEdit($viewer, $task, $report),
            'approve' => $report ? $this->canApprove($viewer, $task, $report) : false,
            // Создатель задания с правом 8 вместо «Отправить» видит «Согласовать» (П-ФК-13): уже при создании отчёта
            'approver' => $this->canApprove($viewer, $task),
            'reject' => $this->canReject($viewer, $task, $report),
            'correct' => $this->canCorrect($viewer, $task, $report),
            'readiness_edit' => $this->canEditReadiness($viewer, $task),
            'declare_overage' => $viewer->rights->has(Right::OverageDeclare),
            'approve_overage' => $viewer->rights->has(Right::OverageApprove),
            'declare_off_plan' => $viewer->rights->has(Right::OffPlanDeclare),
            'approve_off_plan' => $viewer->rights->has(Right::OffPlanApprove),
        ];
    }
}
