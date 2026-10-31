<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Участник чата и его личное состояние: граница прочитанного, закреплён ли чат, без звука ли.
 * В чате объекта участники — мастера бригад, которым поставлен объект, и добавленные вручную.
 */
class MessengerChatMember extends Model
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'member';

    protected $guarded = false;

    protected $casts = [
        'last_read_id' => 'integer',
        'joined_read_id' => 'integer',
        'pinned_at' => 'datetime',
        'archived_at' => 'datetime',
        'muted' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Граница прочитанного при вступлении: сообщения до неё участник не читал (см. миграцию joined_read_id).
        // Массовая вставка (insertOrIgnore) событий не вызывает — там поле заполняется явно
        static::creating(function (MessengerChatMember $member) {
            if (!array_key_exists('joined_read_id', $member->getAttributes())) {
                $member->joined_read_id = $member->last_read_id;
            }
        });
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(MessengerChat::class, 'chat_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}
