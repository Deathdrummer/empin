<?php namespace App\Models;

use App\Models\Traits\Collectionable;
use App\Models\Traits\Filterable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Contract extends Model {
    use HasFactory, Filterable, Collectionable;
	
	
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
		'title',
		'titul',
		'contract',
		'price',
		'date_start',
		'date_end',
		'customer',
		'locality',
		'contractor',
		'type',
		'subcontracting',
		'hoz_method',
		'departments',
		'archive',
		'_sort'
	];
	
	
	
	
	/**
     * Аксессоры, добавляемые к массиву модели.
     *
     * @var array
     */
    protected $appends = ['object_id'];
	
	
	
		
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
			->withPivot('show', 'steps', 'updated_show');
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function getObjectIdAttribute() {
		return Str::of($this->attributes['id'])->padLeft(5, '0');
		//$this->attributes['id'] = Str::of($value)->padLeft(5, '0');
	}
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function setDateStartAttribute($value) {
		$this->attributes['date_start'] = Carbon::parse($value);
	}
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function setDateEndAttribute($value) {
		$this->attributes['date_end'] = Carbon::parse($value);
	}
	
	
}