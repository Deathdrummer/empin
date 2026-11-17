<?php

namespace App\Models\Erp;

use App\Models\Staff;
use App\Production\Rights\Right;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Выдача права приложения сотруднику (ERP, п. 6 «Доработок ERP»; ТЗ, п. 4.2.2).
 * Одно право может быть выдано несколько раз с разными настройками; отзыв — мягкое удаление.
 */
class AppRight extends Model
{
    use SoftDeletes;

    protected $table = 'app_rights';

    protected $guarded = ['id'];

    protected $casts = [
        'right_no' => Right::class,
        'depth_days' => 'integer',
        'scope_all' => 'boolean',
        'staff_ids' => 'array',
        'brigade_ids' => 'array',
        'all_brigades' => 'boolean',
        'date_from' => 'date',
        'date_to' => 'date',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
