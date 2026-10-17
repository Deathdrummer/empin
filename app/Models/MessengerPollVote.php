<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessengerPollVote extends Model
{
    protected $guarded = false;

    protected $casts = [
        'option_index' => 'integer',
    ];
}
