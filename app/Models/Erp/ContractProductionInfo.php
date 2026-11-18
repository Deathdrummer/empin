<?php

namespace App\Models\Erp;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Производственные сведения объекта в ERP (п. 1 «Доработок ERP»): ответственный начальник участка и утверждённая
 * готовность
 */
class ContractProductionInfo extends Model
{
    protected $table = 'contract_production_info';

    protected $primaryKey = 'contract_id';

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'readiness_percent' => 'decimal:2',
        'readiness_changed_at' => 'datetime',
    ];

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'responsible_staff_id');
    }
}
