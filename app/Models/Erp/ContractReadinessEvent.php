<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

/**
 * Изменение готовности объекта (ТЗ П-ФК-34): было → стало, источник, кто (администратор ERP или сотрудник), когда.
 * Записи не меняются и не удаляются.
 */
class ContractReadinessEvent extends Model
{
    public const SOURCE_ERP = 'erp';

    public const UPDATED_AT = null;

    protected $table = 'contract_readiness_events';

    protected $guarded = ['id'];

    protected $casts = [
        'percent_before' => 'decimal:2',
        'percent_after' => 'decimal:2',
        'created_at' => 'datetime',
    ];
}
