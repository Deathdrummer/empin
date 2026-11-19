<?php

namespace App\Models;

use App\Models\Erp\WorkType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Позиция отчёта: вид работ, заявленное и согласованное количество (П-ФК-03, П-ФК-15) */
class TaskReportItem extends Model
{
    public const EXTRA_OVERAGE = 'overage';
    public const EXTRA_OFF_PLAN = 'off_plan';

    public $timestamps = false;

    protected $table = 'task_report_items';

    protected $guarded = ['id'];

    protected $casts = [
        'quantity_declared' => 'float',
        'quantity_approved' => 'float',
        'quantity_in_plan' => 'float',
        'quantity_extra' => 'float',
        'edited_at' => 'datetime',
    ];

    public function workType(): BelongsTo
    {
        return $this->belongsTo(WorkType::class, 'work_type_id')->withTrashed();
    }
}
