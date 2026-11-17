<?php

namespace App\Models;

use App\Models\Erp\WorkType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Вид работ задания из плана объекта с плановым на день количеством (ТЗ, П-ЗД-11) */
class TimesheetContractWork extends Model
{
    protected $table = 'timesheet_contract_works';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    public function workType(): BelongsTo
    {
        return $this->belongsTo(WorkType::class)->withTrashed();
    }
}
