<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Сообщение, удалённое сотрудником «у себя»: у него не показывается нигде (лента, список чатов, поиск, медиа),
 * у остальных участников остаётся.
 */
class MessengerHiddenMessage extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['staff_id', 'chat_id', 'message_id'];

    /** id скрытых сообщений сотрудника в чате — для whereNotIn(...) */
    public static function idsQuery(int $staffId, int $chatId): Builder
    {
        return static::query()->where('staff_id', $staffId)->where('chat_id', $chatId)->select('message_id');
    }

    /**
     * Запрос по нескольким чатам без скрытых сотрудником: $chatColumn и $idColumn — колонки id чата и id
     * сообщения (пара, а не только id: в чате объекта id — это id комментария табеля)
     */
    public static function excludeFor($query, int $staffId, string $chatColumn, string $idColumn)
    {
        return $query->whereNotExists(fn ($hidden) => $hidden->select(DB::raw(1))
            ->from('messenger_hidden_messages as h')
            ->where('h.staff_id', $staffId)
            ->whereColumn('h.chat_id', $chatColumn)
            ->whereColumn('h.message_id', $idColumn));
    }
}
