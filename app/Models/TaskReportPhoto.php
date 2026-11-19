<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Фото отчёта (П-ФК-03) */
class TaskReportPhoto extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'task_report_photos';

    protected $guarded = ['id'];

    protected $casts = ['created_at' => 'datetime'];
}
