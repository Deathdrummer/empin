<?php

namespace App\Production\Tasks;

use App\Models\Erp\BrigadeMember;
use App\Production\Rights\StaffRights;
use Carbon\CarbonInterface;

/** Кто смотрит: сотрудник, его текущие бригады и права из ERP */
final class Viewer
{
    /** @param int[] $brigadeIds бригады, где сотрудник состоит сейчас (доступ — по текущему составу, П-ЗД-22) */
    public function __construct(
        public readonly int $staffId,
        public readonly array $brigadeIds,
        public readonly StaffRights $rights,
    ) {
    }

    public static function load(int $staffId, CarbonInterface $today): self
    {
        $brigadeIds = BrigadeMember::query()
            ->activeOn($today)
            ->where('staff_id', $staffId)
            ->whereHas('brigade', fn ($q) => $q->where(fn ($b) => $b->whereNull('disbanded_on')->orWhere('disbanded_on', '>', $today->toDateString())))
            ->pluck('brigade_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return new self($staffId, $brigadeIds, StaffRights::load($staffId));
    }
}
