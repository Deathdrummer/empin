<?php namespace App\Models;

use App\Models\Traits\Collectionable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractFile extends Model {
    use HasFactory, Collectionable;
	
	
	/**
     * Таблица
	 *
     * @var string
     */
	protected $table = 'contract_files';
	
	
	/**
     * Первичный ключ
	 *
     * @var string
     */
	protected $primaryKey = 'id';
	
	
	/**
	* Атрибуты, для которых НЕ разрешено массовое присвоение значений.
	*
	* @var array
	*/
	protected $guarded = false;
	

	
	/**
     * учитывать временные поля created_at и updated_at
	 *
     * @var string
     */
	public $timestamps = false;
	
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function scopeGetByConmtractId($query, $contractId = null) {
		if (!$contractId) return false;
		return $query->where('contract_id', $contractId);
	}
	
	
}