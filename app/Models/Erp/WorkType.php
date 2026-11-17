<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Вид работ из общего справочника ERP: наименование и единица измерения (П-ОБ-29) */
class WorkType extends Model
{
    use SoftDeletes;

    protected $table = 'work_types';

    protected $guarded = ['id'];
}
