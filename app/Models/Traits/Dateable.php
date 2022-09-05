<?php namespace App\Models\Traits;

use Carbon\Carbon;

trait Dateable {
	
    public function getCreatedAtAttribute($value) {
        return Carbon::create($value)->timezone('Europe/Moscow');
    }
	
	public function getUpdatedAtAttribute($value) {
        return Carbon::create($value)->timezone('Europe/Moscow');
    }
}