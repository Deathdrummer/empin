<?php

namespace App\Production\Rights;

use App\Models\Erp\AppRight;
use Illuminate\Support\Collection;

/**
 * Права сотрудника, полученные из ERP. Отозванные (мягко удалённые) не действуют сразу (П-ПР-24).
 * Сама проверка права выполняется сервером при каждом действии (п. 4.2.2, п. 9.1 правило 4).
 */
final class StaffRights
{
    /** @param Collection<int, Grant> $grants */
    private function __construct(public readonly int $staffId, private readonly Collection $grants)
    {
    }

    public static function load(int $staffId): self
    {
        $grants = AppRight::query()
            ->where('staff_id', $staffId)
            ->orderBy('right_no')
            ->orderBy('id')
            ->get()
            ->filter(fn (AppRight $row) => $row->right_no instanceof Right)
            ->map(fn (AppRight $row) => Grant::fromModel($row))
            ->values();

        return new self($staffId, $grants);
    }

    /** @param Grant[] $grants */
    public static function of(int $staffId, array $grants): self
    {
        return new self($staffId, collect($grants)->values());
    }

    public function has(Right $right): bool
    {
        return $this->grants->contains(fn (Grant $grant) => $grant->right === $right);
    }

    /** @return Collection<int, Grant> */
    public function grantsOf(Right $right): Collection
    {
        return $this->grants->filter(fn (Grant $grant) => $grant->right === $right)->values();
    }

    /** Хотя бы одна выдача права удовлетворяет условию — выдачи складываются */
    public function any(Right $right, callable $check): bool
    {
        return $this->grantsOf($right)->contains(fn (Grant $grant) => (bool) $check($grant));
    }

    /** @return Collection<int, Grant> */
    public function all(): Collection
    {
        return $this->grants;
    }

    /** Версия набора прав: приложение по ней понимает, что права изменились (П-ПР-24) */
    public function version(): string
    {
        return sha1(json_encode($this->grants->map(fn (Grant $grant) => $grant->toArray())->all()));
    }
}
