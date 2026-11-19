<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Самоотметка выхода на смену (П-СМ-01…05, П-СМ-20) */
class AttendanceMark extends Model
{
    protected $table = 'attendance_marks';

    protected $guarded = ['id'];

    protected $casts = [
        'work_date' => 'date',
        'as_driver' => 'boolean',
        'pressed_at' => 'datetime',
        'received_at' => 'datetime',
        'offline' => 'boolean',
        'time_unverified' => 'boolean',
        'after_confirmation' => 'boolean',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}
