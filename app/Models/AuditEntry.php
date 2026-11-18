<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Запись журнала изменений приложения (ТЗ, п. 9.4): кто, что и когда сделал, прежнее и новое значение */
class AuditEntry extends Model
{
    public const ENTITY_TASK = 'task';

    protected $table = 'audit_log';

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'actor_staff_id');
    }
}
