<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Позиция плана объекта: вид работ и плановое количество */
class ContractPlanItem extends Model
{
    protected $table = 'contract_plan_items';

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
