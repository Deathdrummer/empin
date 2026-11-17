<?php

namespace App\Models\Erp;

use App\Models\Staff;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * Период членства сотрудника в бригаде (ERP). История с датами нужна для ТУРВ (П-ОБ-19).
 * joined_on — первый день в бригаде; left_on — дата выхода: с этого дня сотрудник уже не состоит.
 * Пересекающиеся периоды одного сотрудника в одной бригаде недопустимы («дубли связей», п. 3 «Доработок ERP»).
 */
class BrigadeMember extends Model
{
    protected $table = 'brigade_members';

    protected $guarded = ['id'];

    protected $casts = [
        'joined_on' => 'date',
        'left_on' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $member) {
            if ($member->left_on && $member->left_on->lessThanOrEqualTo($member->joined_on)) {
                throw new InvalidArgumentException('Дата выхода из бригады должна быть позже даты вступления');
            }
            $overlap = static::query()
                ->where('brigade_id', $member->brigade_id)
                ->where('staff_id', $member->staff_id)
                ->when($member->exists, fn ($q) => $q->whereKeyNot($member->getKey()))
                ->where(fn ($q) => $q->whereNull('left_on')->orWhere('left_on', '>', $member->joined_on->toDateString()))
                ->when($member->left_on, fn ($q) => $q->where('joined_on', '<', $member->left_on->toDateString()))
                ->exists();
            if ($overlap) {
                throw new InvalidArgumentException('Сотрудник уже состоит в этой бригаде в указанный период');
            }
        });
    }

    public function brigade(): BelongsTo
    {
        return $this->belongsTo(Brigade::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /** Членства, действующие на дату */
    public function scopeActiveOn(Builder $query, CarbonInterface $date): Builder
    {
        $day = $date->toDateString();

        return $query->where('joined_on', '<=', $day)
            ->where(fn ($q) => $q->whereNull('left_on')->orWhere('left_on', '>', $day));
    }
}
