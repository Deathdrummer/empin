<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Уведомление сотруднику в приложении, которое висит до ручного закрытия (ТЗ, п. 9.3) */
class StaffNotification extends Model
{
    public const KIND_TASK_EXECUTOR_CHANGED = 'task_executor_changed';

    // Выход на смену (п. 7.6, п. 9.3)
    public const KIND_CREW_NOT_INCLUDED = 'attendance_not_included';
    public const KIND_CREW_EXCLUDED = 'attendance_excluded';
    public const KIND_MARK_LATE = 'attendance_mark_late';
    public const KIND_ATTENDANCE_REJECTED = 'attendance_rejected';
    public const KIND_DRIVER_NO_TASKS = 'driver_no_tasks';
    public const KIND_DRIVER_ASSIGNED = 'driver_assigned';

    protected $table = 'staff_notifications';

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
        'closed_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('closed_at')->whereNull('withdrawn_at');
    }
}
