<?php

namespace App\Production\Rights;

use App\Models\Erp\PermissionSetting;
use App\Production\Support\DateWindows;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Одна выдача права с настройками (ТЗ, п. 4.2.2). Права независимы (П-ПР-03): каждая выдача проверяется сама по себе,
 * несколько выдач одного права складываются.
 */
final class Grant
{
    /**
     * @param int[] $staffIds выбранные сотрудники: создатели (4, 5, 9), начальники участков (6) или люди (19)
     * @param int[] $brigadeIds выбранные бригады (6, 17, 18, 19)
     */
    public function __construct(
        public readonly Right $right,
        public readonly ?int $depthDays = null,
        public readonly bool $scopeAll = false,
        public readonly array $staffIds = [],
        public readonly array $brigadeIds = [],
        public readonly bool $allBrigades = false,
        public readonly ?CarbonImmutable $dateFrom = null,
        public readonly ?CarbonImmutable $dateTo = null,
    ) {
    }

    /** Выдача права с настройками из ERP */
    public static function fromSetting(Right $right, PermissionSetting $row): self
    {
        return new self(
            right: $right,
            depthDays: $right->hasDepth() ? $row->depth_days : null,
            scopeAll: (bool) $row->scope_all,
            staffIds: array_values(array_map('intval', $row->staff_ids ?? [])),
            brigadeIds: array_values(array_map('intval', $row->brigade_ids ?? [])),
            allBrigades: (bool) $row->all_brigades,
            dateFrom: $row->date_from ? CarbonImmutable::parse($row->date_from->toDateString()) : null,
            dateTo: $row->date_to ? CarbonImmutable::parse($row->date_to->toDateString()) : null,
        );
    }

    /**
     * Право выдано, а настройки не заданы. По словам заказчика настройка обязательна при выдаче (интервью, ответы
     * 7.2 и 8.2: «с обязательной настройкой… не менее 1. Или без ограничений»), значит ERP должна требовать её.
     * Если её всё же нет — страховка: минимальный объём (глубина N = 1, область пустая). Права без настроек
     * (1, 7, 8, 10–16) действуют полностью.
     */
    public static function withoutSettings(Right $right): self
    {
        return new self(right: $right, depthDays: $right->hasDepth() ? 1 : null);
    }

    /** Глубина права пускает к дате (П-ПР-08). У прав без глубины ограничения по дате нет */
    public function allowsDate(CarbonInterface $date, CarbonInterface $today): bool
    {
        return DateWindows::depthAllows($this->right->hasDepth() ? $this->depthDays : null, $date, $today);
    }

    /** Область по создателям заданий: все задания компании или выбранные сотрудники (П-ПР-10) */
    public function coversCreator(int $staffId): bool
    {
        return $this->scopeAll || in_array($staffId, $this->staffIds, true);
    }

    /** Область по бригадам: без ограничений («вся компания»/«все бригады») или выбранные бригады */
    public function coversBrigade(int $brigadeId): bool
    {
        return $this->scopeAll || $this->allBrigades || in_array($brigadeId, $this->brigadeIds, true);
    }

    /** Право 19: дата табеля в пределах «с» и «по» (без ограничений, если не заданы) */
    public function coversViewDate(CarbonInterface $date): bool
    {
        $day = $date->toDateString();

        return (!$this->dateFrom || $day >= $this->dateFrom->toDateString())
            && (!$this->dateTo || $day <= $this->dateTo->toDateString());
    }

    /** Для API и кэша на устройстве (п. 9.1, правило 1) */
    public function toArray(): array
    {
        return [
            'right' => $this->right->value,
            'title' => $this->right->title(),
            'depth_days' => $this->depthDays,
            'scope_all' => $this->scopeAll,
            'staff_ids' => $this->staffIds,
            'brigade_ids' => $this->brigadeIds,
            'all_brigades' => $this->allBrigades,
            'date_from' => $this->dateFrom?->toDateString(),
            'date_to' => $this->dateTo?->toDateString(),
        ];
    }
}
