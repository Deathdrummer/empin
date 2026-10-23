<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Настройки мессенджера сотрудника — одинаковые на всех его устройствах.
 * mute_objects — чаты объектов без звука, и новые тоже приходят без звука;
 * open_chats_on_start — при открытии приложения показывать «Чаты» (по умолчанию — табель).
 */
class MessengerSetting extends Model
{
    protected $primaryKey = 'staff_id';

    public $incrementing = false;

    protected $guarded = false;

    protected $casts = [
        'mute_objects' => 'boolean',
        'open_chats_on_start' => 'boolean',
    ];

    public static function muteObjects(int $staffId): bool
    {
        return (bool) static::whereKey($staffId)->value('mute_objects');
    }
}
