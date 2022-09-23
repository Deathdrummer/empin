<?php namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ContractDepartment extends Pivot {
	
	
	
	/**
     * учитывать временные поля created_at и updated_at
	 *
     * @var string
     */
	public $timestamps = false;
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function getStepsAttribute($value) {
		$data = json_decode($value, true);
		if (!$data) return null;
		
		$result = [];
		foreach ($data as $step) {
			$result[$step['step_id']] = $step;
		}
		return $result;
	}
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function getUpdatedShowAtAttribute($value) {
		return Carbon::create($value)->timezone('Europe/Moscow');
	}
	
	
}