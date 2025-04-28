<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpenAIThread extends Model
{
	protected $table = 'openai_threads';
	
	protected $fillable = ['thread_id'];
	public $timestamps = true;
}