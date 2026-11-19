<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Отчёт по заданию (ТЗ, раздел 8): черновик → отправлен → согласован или отклонён (П-ФК-08) */
class TaskReport extends Model
{
    public const DRAFT = 'draft';
    public const SUBMITTED = 'submitted';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    protected $table = 'task_reports';

    protected $guarded = ['id'];

    protected $casts = [
        'task_percent_declared' => 'float',
        'task_percent_approved' => 'float',
        'object_percent_prefilled' => 'float',
        'object_percent_declared' => 'float',
        'object_percent_approved' => 'float',
        'submitted_at' => 'datetime',
        'decided_at' => 'datetime',
        'edited_at' => 'datetime',
        'author_locked_at' => 'datetime',
        'revision' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(TimesheetContract::class, 'task_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TaskReportItem::class, 'report_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(TaskReportPhoto::class, 'report_id');
    }

    /** Отчёт предоставлен — отправлен или согласован; черновик и отклонённый — нет (П-ЗД-37) */
    public function provided(): bool
    {
        return in_array($this->status, [self::SUBMITTED, self::APPROVED], true);
    }

    /** Отчёт ещё не согласован: его правит автор, согласующий его согласует (П-ФК-10, П-ФК-13) */
    public function open(): bool
    {
        return $this->status !== self::APPROVED;
    }
}
