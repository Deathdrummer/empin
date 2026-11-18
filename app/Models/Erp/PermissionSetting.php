<?php

namespace App\Models\Erp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Permission;

/** Настройки выданного права ERP: глубина, область, даты (ТЗ, п. 4.2.2) */
class PermissionSetting extends Model
{
    protected $table = 'permission_settings';

    protected $guarded = ['id'];

    protected $casts = [
        'depth_days' => 'integer',
        'scope_all' => 'boolean',
        'staff_ids' => 'array',
        'brigade_ids' => 'array',
        'all_brigades' => 'boolean',
        'date_from' => 'date',
        'date_to' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    /** Для истории: только сами настройки */
    public function snapshot(): array
    {
        return [
            'depth_days' => $this->depth_days,
            'scope_all' => (bool) $this->scope_all,
            'staff_ids' => $this->staff_ids ?? [],
            'brigade_ids' => $this->brigade_ids ?? [],
            'all_brigades' => (bool) $this->all_brigades,
            'date_from' => $this->date_from?->toDateString(),
            'date_to' => $this->date_to?->toDateString(),
        ];
    }
}
