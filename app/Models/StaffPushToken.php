<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffPushToken extends Model
{
    protected $guarded = false;

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
