<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

/** Настройки права в типовом наборе (роль `app-set-…`): копируются сотруднику при применении набора (ТЗ, п. 4.2.3) */
class RolePermissionSetting extends Model
{
    protected $table = 'role_permission_settings';

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
