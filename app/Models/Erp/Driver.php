<?php

namespace App\Models\Erp;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Водитель из списка водителей ERP (из числа сотрудников); своя отметка «не используется» (П-ОБ-23а) */
class Driver extends Model
{
    use SoftDeletes;

    protected $table = 'drivers';

    protected $guarded = ['id'];

    protected $casts = [
        'not_used' => 'boolean',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(Vehicle::class, 'vehicle_drivers')->withTimestamps();
    }
}
