<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Машина спецтехники (ERP, п. 4 «Доработок ERP») */
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
