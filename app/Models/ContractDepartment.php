<?php namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ContractDepartment extends Pivot {
	
	/**
     * Таблица
	 *
     * @var string
     */
	protected $table = 'contract_department';
	
	
	/**
	* Атрибуты, для которых НЕ разрешено массовое присвоение значений.
	*
	* @var array
	*/
	protected $guarded = [];
	
	
	
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
		if (!$data || !is_array($data) || empty($data)) return null;
		
		$result = [];
		foreach ($data as $step) {
			$result[$step['step_id']] = $step;
		}
		return $result;
	}
	
	
	
	/**
	 * @param string  $value
	 * @return 
	 */
	public function setStepsAttribute($value) {
		$this->attributes['steps'] = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
	}
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function getUpdatedShowAtAttribute($value) {
		return $value ? Carbon::create($value)->timezone('Europe/Moscow') : null;
	}
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function scopeGetByContractId($query, $contractId = null) {
		return $query->select('show', 'hide')->where('contract_id', $contractId);
	}
	
	
}