<?php

namespace App\Production\Attendance;

use App\Production\Support\DateWindows;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Время и сроки выхода на смену (П-СМ-03, П-СМ-03а, П-СМ-07, П-СМ-10, П-СМ-10а, П-СМ-19; п. 9.1, п. 9.2). Все времена —
 * по серверному времени в часовом поясе организации; допущения раздела 12 (п. 3, 4) — здесь, в одном месте
 */
final class AttendanceRules
{
    /** Окно самоотметки: с 05:00 до 10:00 (10:00 уже нельзя) */
    public const MARK_FROM = '05:00';
    public const MARK_TO = '10:00';

    /** Подтверждение сегодняшнего выхода — с 10:00 */
    public const CONFIRM_FROM = '10:00';

    /** Значок неподтверждённых выходов на сегодняшних заданиях — с 17:00 */
    public const BADGE_FROM = '17:00';

    /** Расхождение часов телефона с серверными, при котором отметка без связи считается проверенной */
    public const CLOCK_TOLERANCE_SECONDS = 300;

    public static function markWindowOpen(CarbonInterface $at): bool
    {
        $time = $at->format('H:i');

        return $time >= self::MARK_FROM && $time < self::MARK_TO;
    }

    /** Заявка состава — в день смены с 05:00 до конца дня (П-СМ-07) */
    public static function crewWindowOpen(CarbonInterface $at): bool
    {
        return $at->format('H:i') >= self::MARK_FROM;
    }

    /** Сегодняшний выход подтверждается с 10:00; будущий — нет */
    public static function confirmTimeAllows(CarbonInterface $date, CarbonInterface $at): bool
    {
        $day = $date->toDateString();
        $today = $at->toDateString();

        return $day < $today || ($day === $today && $at->format('H:i') >= self::CONFIRM_FROM);
    }

    /** Срок создателя задания и подтверждения водителя: сегодня и три предыдущих дня (п. 9.2, правило 3 — вывод) */
    public static function creatorDaysAllow(CarbonInterface $date, CarbonInterface $today): bool
    {
        return DateWindows::daysBackAllows(DateWindows::CREATOR_CONFIRM_DAYS_BACK, $date, $today);
    }

    /** Значок: прошлые дни — сразу, сегодняшний — с 17:00 (П-СМ-19) */
    public static function badgeTimeAllows(CarbonInterface $date, CarbonInterface $at): bool
    {
        $day = $date->toDateString();
        $today = $at->toDateString();

        return $day < $today || ($day === $today && $at->format('H:i') >= self::BADGE_FROM);
    }

    /**
     * Когда засчитывается действие (П-СМ-03а, п. 9.1, правило 2). При связи — по серверному времени. Без связи — по
     * времени нажатия на телефоне; если часы телефона расходятся с серверными больше чем на 5 минут, действие
     * принимается с пометкой «время не проверено».
     *
     * @return array{at: CarbonImmutable, offline: bool, unverified: bool}
     */
    public static function actionTime(bool $offline, ?string $pressedAt, ?string $deviceNow, CarbonImmutable $serverNow): array
    {
        if (!$offline || !$pressedAt) {
            return ['at' => $serverNow, 'offline' => false, 'unverified' => false];
        }
        $tz = $serverNow->getTimezone();
        $pressed = CarbonImmutable::parse($pressedAt)->setTimezone($tz);
        $device = $deviceNow ? CarbonImmutable::parse($deviceNow)->setTimezone($tz) : null;
        $unverified = !$device || abs($serverNow->getTimestamp() - $device->getTimestamp()) > self::CLOCK_TOLERANCE_SECONDS;

        return ['at' => $pressed, 'offline' => true, 'unverified' => $unverified];
    }
}
