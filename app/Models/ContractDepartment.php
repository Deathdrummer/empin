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
     * Атрибуты, которые должны быть типизированы. (Конвертация полей при добавлении и получении)
	 *
     * @var array
     */
	protected $casts = [
        'steps' => 'array',
    ];
	
	
	

	
	
	
	
	
	
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