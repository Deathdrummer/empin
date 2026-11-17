<?php

namespace App\Production\Tasks;

use App\Production\Rights\Grant;
use App\Production\Rights\Right;
use Carbon\CarbonInterface;

/**
 * Доступ к заданию по ТЗ (п. 4.2.1, 4.2.2, П-ПР-05…12, П-ЗД-20…29а). Сервер проверяет его при каждом действии.
 */
final class TaskAccess
{
    public const BASE_CREATOR = 'creator';
    public const BASE_EXECUTOR = 'executor';
    public const BASE_BRIGADE = 'brigade';
    public const BASE_DRIVER = 'driver';
    public const BASE_INVITED = 'invited';
    public const BASE_VIEW_ALL = 'view_all';

    /**
     * Основания видеть задание (П-ЗД-20). Доступ — объединение всех оснований (П-ПР-05).
     * Закрепление объекта за начальником участка оснований не даёт (П-ЗД-21).
     *
     * @return string[]
     */
    public function bases(Viewer $viewer, TaskFacts $task): array
    {
        $me = $viewer->staffId;
        $bases = [];
        if ($task->creatorId === $me) {
            $bases[] = self::BASE_CREATOR;
        }
        if ($task->brigadeId === null && $task->personalStaffId === $me) {
            $bases[] = self::BASE_EXECUTOR;
        }
        if ($task->brigadeId !== null && in_array($task->brigadeId, $viewer->brigadeIds, true)) {
            $bases[] = self::BASE_BRIGADE;
        }
        if ($task->driverStaffId === $me) {
            $bases[] = self::BASE_DRIVER;
        }
        if (in_array($me, $task->invitedStaffIds, true)) {
            $bases[] = self::BASE_INVITED;
        }
        if ($viewer->rights->any(Right::TaskViewAll, fn (Grant $g) => $this->viewAllCovers($g, $task))) {
            $bases[] = self::BASE_VIEW_ALL;
        }

        return $bases;
    }

    public function canView(Viewer $viewer, TaskFacts $task): bool
    {
        return $this->bases($viewer, $task) !== [];
    }

    /**
     * Изменить задание (права 2 и 4). Глубина проверяется для текущей даты задания, а при переносе — и для новой
     * (П-ПР-08, раздел 12 п. 28). Права 4 и 5 просмотра не дают (П-ПР-05а).
     */
    public function canEdit(Viewer $viewer, TaskFacts $task, CarbonInterface $today, ?CarbonInterface $newDate = null): bool
    {
        return $this->canChange($viewer, $task, $today, Right::TaskEditOwn, Right::TaskEditOthers, $newDate);
    }

    /** Удалить задание (права 3 и 5) — своя глубина, независимая от изменения (П-ПР-03) */
    public function canDelete(Viewer $viewer, TaskFacts $task, CarbonInterface $today): bool
    {
        return $this->canChange($viewer, $task, $today, Right::TaskDeleteOwn, Right::TaskDeleteOthers);
    }

    /** Пригласить в задание и отозвать приглашение (право 7) — в заданиях, которые сотрудник видит (вывод, раздел 12 п. 29) */
    public function canInvite(Viewer $viewer, TaskFacts $task): bool
    {
        return $viewer->rights->has(Right::TaskInvite) && $this->canView($viewer, $task);
    }

    /**
     * Создать задание (право 1): сегодня или вперёд (П-ЗД-01, ИС-ЗД-01), только на активный объект
     * (П-ЗД-05; архив — раздел 12 п. 30)
     */
    public function canCreate(Viewer $viewer, CarbonInterface $date, CarbonInterface $today, bool $objectActive): bool
    {
        return $viewer->rights->has(Right::TaskCreate)
            && $date->toDateString() >= $today->toDateString()
            && $objectActive;
    }

    /** Скопировать задание: видеть исходное и иметь право создать новое (П-ЗД-03) */
    public function canCopy(Viewer $viewer, TaskFacts $source, CarbonInterface $date, CarbonInterface $today): bool
    {
        return $this->canView($viewer, $source) && $this->canCreate($viewer, $date, $today, $source->objectActive);
    }

    /** Признаки для приложения — чтобы не дублировать правила на телефоне (окончательная проверка — сервер) */
    public function abilities(Viewer $viewer, TaskFacts $task, CarbonInterface $today): array
    {
        return [
            'bases' => $this->bases($viewer, $task),
            'edit' => $this->canEdit($viewer, $task, $today),
            'delete' => $this->canDelete($viewer, $task, $today),
            'invite' => $this->canInvite($viewer, $task),
            'copy' => $viewer->rights->has(Right::TaskCreate) && $task->objectActive && $this->canView($viewer, $task),
        ];
    }

    private function canChange(Viewer $viewer, TaskFacts $task, CarbonInterface $today, Right $own, Right $others, ?CarbonInterface $newDate = null): bool
    {
        if (!$this->canView($viewer, $task)) {
            return false;
        }
        $dates = array_filter([$task->date, $newDate]);
        $datesAllowed = fn (Grant $g) => collect($dates)->every(fn ($d) => $g->allowsDate($d, $today));

        if ($task->creatorId === $viewer->staffId) {
            return $viewer->rights->any($own, $datesAllowed);
        }

        return $viewer->rights->any($others, fn (Grant $g) => $this->creatorCovered($g, $task->creatorId) && $datesAllowed($g));
    }

    /** Область «все задания компании или выбранные создатели» (П-ПР-10); у записей без создателя — только «все» */
    private function creatorCovered(Grant $grant, ?int $creatorId): bool
    {
        return $creatorId === null ? $grant->scopeAll : $grant->coversCreator($creatorId);
    }

    /** Право 6: все задания; или созданные выбранными начальниками участков; или где исполнитель — выбранная бригада */
    private function viewAllCovers(Grant $grant, TaskFacts $task): bool
    {
        return $grant->scopeAll
            || ($task->creatorId !== null && in_array($task->creatorId, $grant->staffIds, true))
            || ($task->brigadeId !== null && in_array($task->brigadeId, $grant->brigadeIds, true));
    }
}
