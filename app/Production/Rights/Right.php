<?php

namespace App\Production\Rights;

/**
 * Каталог назначаемых прав приложения — ТЗ, п. 4.2.2. Номер совпадает с номером в ТЗ.
 * Какие настройки есть у права — там же, колонка «Настройки».
 */
enum Right: int
{
    case TaskCreate = 1;
    case TaskEditOwn = 2;
    case TaskDeleteOwn = 3;
    case TaskEditOthers = 4;
    case TaskDeleteOthers = 5;
    case TaskViewAll = 6;
    case TaskInvite = 7;
    case ReportSubmit = 8;
    case ReportApproveOthers = 9;
    case ReportEditApproved = 10;
    case OverageDeclare = 11;
    case OverageApprove = 12;
    case OffPlanDeclare = 13;
    case OffPlanApprove = 14;
    case ReadinessEdit = 15;
    case CrewDeclare = 16;
    case AttendanceConfirmOthers = 17;
    case AttendanceCorrect = 18;
    case TimesheetView = 19;

    public function title(): string
    {
        return match ($this) {
            self::TaskCreate => 'Создавать задания',
            self::TaskEditOwn => 'Изменять свои задания',
            self::TaskDeleteOwn => 'Удалять свои задания',
            self::TaskEditOthers => 'Изменять чужие задания',
            self::TaskDeleteOthers => 'Удалять чужие задания',
            self::TaskViewAll => 'Просматривать все задания',
            self::TaskInvite => 'Открывать доступ к заданию',
            self::ReportSubmit => 'Заявлять отчёт',
            self::ReportApproveOthers => 'Согласовывать чужие отчёты',
            self::ReportEditApproved => 'Изменять согласованный отчёт',
            self::OverageDeclare => 'Заявлять объём сверх проекта',
            self::OverageApprove => 'Согласовывать объём сверх проекта',
            self::OffPlanDeclare => 'Заявлять работы вне плана объекта',
            self::OffPlanApprove => 'Согласовывать работы вне плана объекта',
            self::ReadinessEdit => 'Изменять готовность объекта напрямую',
            self::CrewDeclare => 'Заявлять состав бригады',
            self::AttendanceConfirmOthers => 'Подтверждать выходы в чужих заданиях',
            self::AttendanceCorrect => 'Корректировать выходы',
            self::TimesheetView => 'Просматривать ТУРВ',
        };
    }

    /** Есть ли у права глубина «N дней или без ограничений» (права 2, 3, 4, 5, 17, 18) */
    public function hasDepth(): bool
    {
        return in_array($this, [
            self::TaskEditOwn, self::TaskDeleteOwn, self::TaskEditOthers, self::TaskDeleteOthers,
            self::AttendanceConfirmOthers, self::AttendanceCorrect,
        ], true);
    }

    /** Область по создателям заданий: все задания компании или выбранные сотрудники (П-ПР-10; права 4, 5, 9) */
    public function hasCreatorScope(): bool
    {
        return in_array($this, [self::TaskEditOthers, self::TaskDeleteOthers, self::ReportApproveOthers], true);
    }

    /** Область по бригадам или без ограничений (права 17, 18) */
    public function hasBrigadeScope(): bool
    {
        return in_array($this, [self::AttendanceConfirmOthers, self::AttendanceCorrect], true);
    }

    /**
     * Права, которые просмотра не дают и действуют только в заданиях, которые сотрудник видит
     * (П-ПР-05а, раздел 12 п. 29 — вывод): 4, 5, 7, 9, 10, 15
     */
    public function requiresVisibility(): bool
    {
        return in_array($this, [
            self::TaskEditOthers, self::TaskDeleteOthers, self::TaskInvite,
            self::ReportApproveOthers, self::ReportEditApproved, self::ReadinessEdit,
        ], true);
    }
}
