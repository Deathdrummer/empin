<?php

namespace App\Production\Support;

use Carbon\CarbonInterface;

/**
 * Сроки по датам из ТЗ. Сравниваются только календарные даты (время суток — отдельные правила разделов 7 и 8).
 */
final class DateWindows
{
    /** Срок «до 3 дней назад» для подтверждения выхода создателем и водителя (п. 9.2, правило 3 — вывод) */
    public const CREATOR_CONFIRM_DAYS_BACK = 3;

    /**
     * Глубина права «N дней, считая сегодняшний» (П-ПР-08, п. 9.2, правило 2): при N = 3 и сегодня 26.10
     * доступны 24, 25 и 26.10. Будущие даты доступны всегда; NULL — без ограничений.
     */
    public static function depthAllows(?int $depthDays, CarbonInterface $date, CarbonInterface $today): bool
    {
        if ($depthDays === null || self::day($date) > self::day($today)) {
            return true;
        }

        return self::day($date) >= self::day($today->copy()->subDays(max(1, $depthDays) - 1));
    }

    /**
     * «С сегодняшнего дня до 3 дней назад» — сегодня и три предыдущих календарных дня: при 26.10 — 23–26.10
     * (п. 9.2, правило 3). На один день больше, чем глубина N = 3. Будущие даты сюда не входят.
     */
    public static function daysBackAllows(int $daysBack, CarbonInterface $date, CarbonInterface $today): bool
    {
        $day = self::day($date);

        return $day <= self::day($today) && $day >= self::day($today->copy()->subDays($daysBack));
    }

    private static function day(CarbonInterface $date): string
    {
        return $date->toDateString();
    }
}
