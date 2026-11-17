<?php

namespace App\Production\Tasks;

use App\Models\TimesheetTeam;

/** Исполнитель задания: бригада либо конкретный сотрудник (П-ЗД-12) */
final class Executor
{
    private function __construct(public readonly ?int $brigadeId, public readonly ?int $staffId)
    {
    }

    public static function brigade(int $brigadeId): self
    {
        return new self($brigadeId, null);
    }

    public static function staff(int $staffId): self
    {
        return new self(null, $staffId);
    }

    /** Исполнитель записи план-графика: у бригады `staff_id` — служебный (бригадир), исполнитель — бригада */
    public static function ofTeam(TimesheetTeam $team): self
    {
        return $team->brigade_id ? self::brigade((int) $team->brigade_id) : self::staff((int) $team->staff_id);
    }

    public function isBrigade(): bool
    {
        return $this->brigadeId !== null;
    }

    public function equals(self $other): bool
    {
        return $this->brigadeId === $other->brigadeId && $this->staffId === $other->staffId;
    }
}
