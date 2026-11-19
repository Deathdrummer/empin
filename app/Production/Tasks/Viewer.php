<?php

namespace App\Production\Tasks;

use App\Production\Erp\ErpGateway;
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
        // Член по составу или бригадир (П-ОБ-16); расформированная бригада не действует
        $brigadeIds = array_keys(app(ErpGateway::class)->brigadesOfStaff($staffId, $today));

        return new self($staffId, $brigadeIds, StaffRights::load($staffId));
    }
}
