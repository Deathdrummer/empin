<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Сотрудника убрали из чата объекта (или он вышел сам): табель не вернёт его в чат,
 * пока объект не поставят его бригаде снова — позже этой отметки.
 */
class MessengerChatRemoval extends Model
{
    protected $guarded = false;
}
