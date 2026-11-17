<?php

namespace App\Models\Erp;

use App\Models\Staff;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Бригада (ERP). Постоянный коллектив 2–5 человек; бригадир — только в одной бригаде (П-ОБ-16, П-ОБ-17).
 * Не удаляется, а расформировывается: прежние задания, отчёты и выходы сохраняются (п. 10.3 ТЗ).
 */
class Brigade extends Model
{
    protected $table = 'brigades';

    protected $guarded = ['id'];

    protected $casts = [
        'disbanded_on' => 'date',
    ];

    public function foreman(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'foreman_staff_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(BrigadeMember::class);
    }

    /** Действующие на дату членства */
    public function membersOn(CarbonInterface $date): HasMany
    {
        return $this->memberships()->activeOn($date);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('disbanded_on');
    }
}
