<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Настройки мессенджера сотрудника — одинаковые на всех его устройствах.
 * mute_objects — чаты объектов без звука, и новые тоже приходят без звука.
 */
class MessengerSetting extends Model
{
    protected $primaryKey = 'staff_id';

    public $incrementing = false;

    protected $guarded = false;

    protected $casts = [
        'mute_objects' => 'boolean',
    ];

    public static function muteObjects(int $staffId): bool
    {
        return (bool) static::whereKey($staffId)->value('mute_objects');
    }
}
