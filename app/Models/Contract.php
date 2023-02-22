<?php namespace App\Models;

use App\Models\Traits\Collectionable;
use App\Models\Traits\Dateable;
use App\Models\Traits\Filterable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Contract extends Model {
    use HasFactory, Filterable, Collectionable, Dateable;
	
	
	/**
     * Таблица
	 *
     * @var string
     */
	protected $table = 'contracts';
	
	
	/**
     * Первичный ключ
	 *
     * @var string
     */
	protected $primaryKey = 'id';
	
	
	
	/**
     * Атрибуты, которые должны быть типизированы. (Конвертация полей при добавлении и получении)
	 *
     * @var array
     */
	//protected $casts = [
        //'date_start' => 'timestamp',
        //'date_end'	 => 'timestamp',
    //];
	
	
	
	/**
     * Тип первичного ключа
	 *
     * @var string
     */
	protected $keyType = 'integer';
	
	
	
	/**
     * Поля разрешенные для редактирования
	 *
     * @var array
     */
	protected $fillable = [
		'object_number',
		'buy_number',
		'without_buy',
		'title',
		'applicant',
		'titul',
		'contract',
		'price',
		'price_nds',
		'price_gen',
		'price_gen_nds',
		'price_sub',
		'price_sub_nds',
		'gen_percent',
		'date_start',
		'date_end',
		'date_gen_start',
		'date_gen_end',
		'date_sub_start',
		'date_sub_end',
		'date_close',
		'date_buy',
		'customer',
		'locality',
		'contractor',
		'type',
		'subcontracting',
		'gencontracting',
		'hoz_method',
		'departments',
		'archive',
		'archive_dir',
		'_sort'
	];
	
	
	
	
	
		
	/**
     * Установка значений в момент создания модели
     *
     * @var array
     */
	public static function boot() {
    	parent::boot();
		self::creating(function ($model) {
			$model->archive = 0;
		});
		
		self::updating(function ($model) {
			if (!$model['subcontracting'] && !$model['gencontracting']) {
				$model->price_gen = null;
				$model->price_gen_nds =null;
				$model->price_sub = null;
				$model->price_sub_nds = null;
			
			} else if (!$model['subcontracting'] && $model['gencontracting']) {
				$model->price_gen = null;
				$model->price_gen_nds = null;
			
			} else if ($model['subcontracting'] && !$model['gencontracting']) {
				$model->price_sub = null;
				$model->price_sub_nds = null;
				
			}
		});
	}
	
	
	
	
	
	/**
     * Отделы договора.
     */
	public function departments() {
		return $this->belongsToMany(
			Department::class,
			'contract_department',
			'contract_id',
			'department_id',
			'id',
			'id')
			->as('pivot')
			->using(ContractDepartment::class)
			->withPivot('show', 'hide', 'steps', 'updated_show');
	}
	
	
	/**
     * Информация 
     */
	public function info() {
		return $this->hasOne(ContractInfo::class, 'contract_id', 'id');
	}
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function selections() {
		return $this->belongsToMany(
			Selection::class,
			'contract_selection_contract',
			'contract_id',
			'selection_id',
			'id',
			'id');
	}
	
	
	
	
	
	
	/**
     * Аккаунты договора
     */
	public function accounts() {
		return $this->belongsToMany(
			User::class,
			'user_contract',
			'account_id',
			'contract_id',
			'id',
			'id')
			->as('pivot')
			->withPivot('viewed', 'pinned');
	}
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function messages() {
		return $this->hasMany(ContractChat::class, 'contract_id', 'id');
	}
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function setDateStartAttribute($value) {
		$this->attributes['date_start'] = $value ? Carbon::parse($value) : null;
	}
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function setDateEndAttribute($value) {
		$this->attributes['date_end'] = $value ? Carbon::parse($value) : null;
	}
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function setDateGenStartAttribute($value) {
		$this->attributes['date_gen_start'] = $value ? Carbon::parse($value) : null;
	}
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function setDateGenEndAttribute($value) {
		$this->attributes['date_gen_end'] = $value ? Carbon::parse($value) : null;
	}
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function setDateSubStartAttribute($value) {
		$this->attributes['date_sub_start'] = $value ? Carbon::parse($value) : null;
	}
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function setDateSubEndAttribute($value) {
		$this->attributes['date_sub_end'] = $value ? Carbon::parse($value) : null;
	}
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function setDateCloseAttribute($value) {
		$this->attributes['date_close'] = $value ? Carbon::parse($value) : null;
	}
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function setDateBuyAttribute($value) {
		$this->attributes['date_buy'] = $value ? Carbon::parse($value) : null;
	}
	
	
	
}