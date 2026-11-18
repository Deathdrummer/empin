<?php

namespace App\Production\Tasks;

use App\Models\Erp\Brigade;
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
            ->whereHas('brigade', fn ($q) => $q->workingOn($today))
            ->pluck('brigade_id')
            // Бригадир — в составе своей бригады, даже если ERP не внесла его строкой состава (П-ОБ-16)
            ->merge(Brigade::where('foreman_staff_id', $staffId)->workingOn($today)->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return new self($staffId, $brigadeIds, StaffRights::load($staffId));
    }
}
