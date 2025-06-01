<?php namespace App\Models;

use App\Models\Traits\Collectionable;
use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ContractFile extends Model {
    use HasFactory, Collectionable, Filterable;
	
	
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
	 * @param 
	 * @return 
	 */
	public function contract():HasOne {
		return $this->hasOne(Contract::class, 'id', 'contract_id')->select(['id', 'object_number', 'applicant', 'archive']);
	}
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function author():HasOne {
		return $this->hasOne(User::class, 'id', 'from_id')->select(['id', 'name', 'pseudoname']);
	}
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function scopeGetByContractId($query, $contractId = null) {
		if (!$contractId) return false;
		return $query->where('contract_id', $contractId);
	}
	
	
}