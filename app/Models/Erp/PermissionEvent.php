<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

/** Запись истории прав ERP: кто, кому, что и когда («Доработки ERP», п. 6; П-ПР-25) */
class PermissionEvent extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'permission_events';

    protected $guarded = ['id'];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'created_at' => 'datetime',
    ];
}
