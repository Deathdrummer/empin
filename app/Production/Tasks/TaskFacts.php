<?php

namespace App\Production\Tasks;

use App\Models\TimesheetContract;
use Carbon\CarbonImmutable;

/**
 * Сведения о задании, нужные для проверки доступа. Задание — запись план-графика ERP (timesheet_contracts):
 * объект на дату исполнителя (timesheet_teams).
 */
final class TaskFacts
{
    /** @param int[] $invitedStaffIds действующие (не отозванные) приглашения */
    public function __construct(
        public readonly int $id,
        public readonly CarbonImmutable $date,
        public readonly ?int $creatorId,
        public readonly ?int $brigadeId,
        public readonly ?int $personalStaffId,
        public readonly ?int $driverStaffId = null,
        public readonly array $invitedStaffIds = [],
        public readonly bool $objectActive = true,
    ) {
    }

    public static function fromModel(TimesheetContract $task): self
    {
        $task->loadMissing(['team', 'activeInvites']);
        $team = $task->team;
        $brigadeId = $team->brigade_id ? (int) $team->brigade_id : null;

        return new self(
            id: (int) $task->id,
            date: CarbonImmutable::parse($team->day)->startOfDay(),
            creatorId: $task->creator_staff_id ? (int) $task->creator_staff_id : null,
            brigadeId: $brigadeId,
            personalStaffId: $brigadeId ? null : (int) $team->staff_id,
            driverStaffId: $task->driver_staff_id ? (int) $task->driver_staff_id : null,
            invitedStaffIds: $task->activeInvites->pluck('staff_id')->map(fn ($id) => (int) $id)->all(),
            objectActive: (int) \DB::table('contracts')->where('id', $task->contract_id)->value('archive') === 0,
        );
    }
}
