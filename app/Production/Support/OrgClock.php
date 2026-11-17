<?php

namespace App\Production\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Время организации. Все ограничения по времени (05:00–10:00, с 10:00, 09:00, с 17:00) считаются по серверному
 * времени в часовом поясе организации (ТЗ, п. 9.2, правило 1; раздел 12, п. 16 — вывод).
 * Часового пояса организации в ERP нет — он задаётся настройкой `production.timezone`.
 */
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
