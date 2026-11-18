<?php

namespace App\Production\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/** Время организации */
class OrgClock
{
    public function timezone(): string
    {
        return (string) config('production.timezone');
    }

    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone());
    }

    public function today(): CarbonImmutable
    {
        return $this->now()->startOfDay();
    }

    /** Календарная дата в часовом поясе организации (без времени) */
    public function dateOf(CarbonInterface|string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date instanceof CarbonInterface ? $date->toDateString() : $date, $this->timezone())->startOfDay();
    }
}
