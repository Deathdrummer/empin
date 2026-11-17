<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Уведомление сотруднику в приложении, которое висит до ручного закрытия (ТЗ, п. 9.3).
 * Остаётся у получателя, даже если доступа к заданию больше нет (П-ЗД-32), поэтому task_id — без внешнего ключа.
 * `withdrawn_at` — снято системой (например, «нет заданий» после назначения водителя, П-ЗД-19).
 */
class StaffNotification extends Model
{
    public const KIND_TASK_EXECUTOR_CHANGED = 'task_executor_changed';

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
