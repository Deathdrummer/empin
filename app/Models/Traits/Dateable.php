<?php namespace App\Models\Traits;

use Carbon\Carbon;

trait Dateable {
	
    public function getCreatedAtAttribute($value) {
		if (!$value) return false;
        return Carbon::create($value)->timezone('Europe/Moscow');
    }
	
	public function getUpdatedAtAttribute($value) {
		if (!$value) return false;
        return Carbon::create($value)->timezone('Europe/Moscow');
    }
}