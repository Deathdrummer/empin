<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Порядок бригад и строк ТУРВ за учётной записью, общий для всех месяцев (П-СМ-33) */
class TurvOrder extends Model
{
    protected $table = 'turv_orders';

    protected $guarded = ['id'];

    protected $casts = [
        'brigade_ids' => 'array',
        'rows' => 'array',
    ];
}
