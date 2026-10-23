<?php

namespace App\Services\Messenger;

/**
 * Реакции на сообщения и комментарии табеля: от одного человека — одна реакция, как в WhatsApp.
 * Та же реакция снимается, другая заменяет прежнюю.
 */
class Reactions
{
    /**
     * @param array<int, array{user_id: int|string, emoji: string}> $reactions
     * @return array<int, array{user_id: int, emoji: string}>
     */
    public static function toggle(array $reactions, int $userId, string $emoji): array
    {
        $mine = collect($reactions)->first(fn (array $reaction) => (int) $reaction['user_id'] === $userId && $reaction['emoji'] === $emoji);
        $others = array_values(array_filter($reactions, fn (array $reaction) => (int) $reaction['user_id'] !== $userId));

        if (!$mine) {
            $others[] = ['user_id' => $userId, 'emoji' => $emoji];
        }

        return $others;
    }
}
