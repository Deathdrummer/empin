<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Приглашение в задание (ТЗ, П-ЗД-24): полный просмотр и переписка без права менять задание и отчёт.
 * Отзыв закрывает доступ, сообщения приглашённого остаются; запись не удаляется — история.
 */
class TimesheetContractInvite extends Model
{
    protected $table = 'timesheet_contract_invites';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'invited_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
