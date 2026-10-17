<?php

namespace App\Services\Messenger;

use App\Models\Staff;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

/**
 * «В сети» / «был в 12:40»: время последнего запроса сотрудника к API.
 * Открытое приложение раз в минуту отмечается само, поэтому «в сети» — активность за последние 2 минуты.
 */
class Presence
{
    public const ONLINE_SECONDS = 120;
    // Чаще раза в минуту время в базе не обновляем
    private const TOUCH_INTERVAL = 60;

    public static function touch(?int $staffId): void
    {
        if (!$staffId || !Cache::add('messenger:seen:' . $staffId, 1, self::TOUCH_INTERVAL)) {
            return;
        }

        Staff::whereKey($staffId)->update(['last_seen_at' => now()]);
    }

    public static function isOnline(?CarbonInterface $lastSeen): bool
    {
        return $lastSeen !== null && $lastSeen->greaterThanOrEqualTo(now()->subSeconds(self::ONLINE_SECONDS));
    }

    /**
     * @return array{online: bool, last_seen_at: ?string}
     */
    public static function of(?Staff $staff): array
    {
        $lastSeen = $staff?->last_seen_at ? Carbon::parse($staff->last_seen_at) : null;

        return [
            'online'       => self::isOnline($lastSeen),
            'last_seen_at' => $lastSeen?->toIso8601String(),
        ];
    }
}
