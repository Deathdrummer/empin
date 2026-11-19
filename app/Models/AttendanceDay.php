<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Засчитанный рабочий день сотрудника — для ТУРВ (П-СМ-10…13, П-СМ-21) */
class AttendanceDay extends Model
{
    public const SOURCE_CONFIRMED = 'confirmed';
    public const SOURCE_CORRECTED = 'corrected';

    protected $table = 'attendance_days';

    protected $guarded = ['id'];

    protected $casts = [
        'work_date' => 'date',
        'as_driver' => 'boolean',
    ];

    public function by(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'by_staff_id');
    }
}
