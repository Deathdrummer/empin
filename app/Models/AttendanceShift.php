<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Смена бригады на дату (П-СМ-02): заявка состава бригадиром и подтверждение выхода */
class AttendanceShift extends Model
{
    protected $table = 'attendance_shifts';

    protected $guarded = ['id'];

    protected $casts = [
        'work_date' => 'date',
        'crew_staff_ids' => 'array',
        'crew_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'confirmed_offline' => 'boolean',
    ];

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'confirmed_by_staff_id');
    }

    public function crewBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'crew_by_staff_id');
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    /** @return int[]|null */
    public function crew(): ?array
    {
        return $this->crew_staff_ids === null ? null : array_values(array_map('intval', $this->crew_staff_ids));
    }
}
