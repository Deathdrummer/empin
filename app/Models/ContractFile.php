<?php namespace App\Models;

use App\Models\Traits\Collectionable;
use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

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
	public function author():HasOneThrough {
        return $this->hasOneThrough(
            Staff::class, // 1. Конечная модель, которую мы хотим получить (Staff).
            User::class,  // 2. Промежуточная модель, через которую идем (User).

            // --- Ключи связи ---
            'id',       // 3. Ключ на промежуточной таблице `users` (связан с `from_id`).
            'id',       // 4. Ключ на конечной таблице `staff` (связан с `staff_id`).
            'from_id',  // 5. Ключ на текущей модели (`contract_files`), который ссылается на `users`.
            'staff_id'  // 6. Ключ на промежуточной таблице `users`, который ссылается на `staff`.
        )->selectRaw("staff.id, CONCAT(staff.sname, ' ', staff.fname) as full_name");
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