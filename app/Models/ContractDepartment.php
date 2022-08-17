<?php namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ContractDepartment extends Pivot {
	
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
}