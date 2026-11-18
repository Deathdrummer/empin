<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Редакция плана объекта (ERP). Актуальная — последняя по объекту; изменение плана действует сразу,
 * прежние редакции сохраняются (П-ОБ-30, П-ОБ-32, п. 11.2 ТЗ).
 */
class ContractPlanRevision extends Model
{
    protected $table = 'contract_plan_revisions';

    protected $guarded = ['id'];

    public function items(): HasMany
    {
        return $this->hasMany(ContractPlanItem::class, 'revision_id');
    }

    /**
     * Актуальная редакция плана объекта или null, если плана нет (П-ОБ-31). Пустая последняя редакция — план убран
     * в ERP целиком: это тоже «плана нет»
     */
    public static function currentFor(int $contractId): ?self
    {
        $revision = static::where('contract_id', $contractId)->orderByDesc('id')->first();

        return $revision && $revision->items()->exists() ? $revision : null;
    }
}
