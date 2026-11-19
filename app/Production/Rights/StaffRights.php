<?php

namespace App\Production\Rights;

use App\Production\Erp\ErpGateway;
use Illuminate\Support\Collection;

/** Права сотрудника из ERP: права приложения в механизме прав ERP (личные и через роль) плюс их настройки */
final class StaffRights
{
    /** @param Collection<int, Grant> $grants */
    private function __construct(public readonly int $staffId, private readonly Collection $grants)
    {
    }

    public static function load(int $staffId): self
    {
        $grants = collect(app(ErpGateway::class)->grants($staffId))
            ->map(fn (array $grant) => ['right' => Right::fromPermissionName($grant['permission'])] + $grant)
            ->filter(fn (array $grant) => $grant['right'] !== null)
            ->sortBy(fn (array $grant) => $grant['right']->value)
            ->flatMap(fn (array $grant) => $grant['settings']
                ? array_map(fn (array $row) => Grant::fromSetting($grant['right'], $row), $grant['settings'])
                : [Grant::withoutSettings($grant['right'])])
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
