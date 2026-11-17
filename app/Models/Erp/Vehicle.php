<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Машина спецтехники (ERP, п. 4 «Доработок ERP»). «Не используется» — не предлагается в новых заданиях,
 * но остаётся в прежних (П-ОБ-23а). Удаление мягкое: прежние задания хранят свой снимок (П-ОБ-23б).
 */
class Vehicle extends Model
{
    use SoftDeletes;

    protected $table = 'vehicles';

    protected $guarded = ['id'];

    protected $casts = [
        'not_used' => 'boolean',
    ];

    public function drivers(): BelongsToMany
    {
        return $this->belongsToMany(Driver::class, 'vehicle_drivers')->withTimestamps();
    }
}
