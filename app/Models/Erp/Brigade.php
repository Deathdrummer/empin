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

    /** Бригада действует на дату: не расформирована или расформирована позже */
    public function scopeWorkingOn(Builder $query, CarbonInterface $date): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('disbanded_on')->orWhere('disbanded_on', '>', $date->toDateString()));
    }

    /**
     * Текущий состав бригады — члены на дату и бригадир. Бригада — «2–5 человек, включая бригадира» (П-ОБ-16):
     * бригадир входит в состав, даже если в ERP его забыли внести строкой состава
     *
     * @param int[] $brigadeIds
     * @return array<int, int[]> brigade_id => staff_id[]
     */
    public static function currentStaff(array $brigadeIds, CarbonInterface $date): array
    {
        if (!$brigadeIds) {
            return [];
        }
        $result = array_fill_keys(array_map('intval', $brigadeIds), []);
        BrigadeMember::activeOn($date)->whereIn('brigade_id', $brigadeIds)->get(['brigade_id', 'staff_id'])
            ->each(function (BrigadeMember $member) use (&$result) {
                $result[(int) $member->brigade_id][] = (int) $member->staff_id;
            });
        static::whereIn('id', $brigadeIds)->workingOn($date)->whereNotNull('foreman_staff_id')->get(['id', 'foreman_staff_id'])
            ->each(function (self $brigade) use (&$result) {
                $result[(int) $brigade->id][] = (int) $brigade->foreman_staff_id;
            });

        return array_map(fn (array $ids) => array_values(array_unique($ids)), $result);
    }
}
