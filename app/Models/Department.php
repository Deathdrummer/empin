<?php namespace App\Models;

use App\Models\Traits\Collectionable;
use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Department extends Model {
    use HasFactory, Filterable, Collectionable;
	
	/**
     * Таблица
	 *
     * @var string
     */
	protected $table = 'departments';
	
	
	
	/**
     * Поля разрешенные для редактирования
	 *
     * @var array
     */
	protected $fillable = [
		'name',
		'sort',
		'assigned_primary',
		'show_only_assigned',
		'show_in_timesheet',
		'_sort'
	];
	
	
	
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
	//protected $casts = [
    //    'title' => 'array',
    //    'page_title' => 'array',
    //];
	
	
	
	
	
	
	
	/**
     * Получить этапы к отделу.
     */
    public function steps() {
        return $this->hasMany(Step::class, 'department_id', 'id')/* ->orderBy('_sort', 'ASC') */;
    }
	
	
	
	
	/**
     * Получить этапы к отделу.
     */
    public function users() {
        return $this->hasMany(User::class, 'department_id', 'id')/* ->orderBy('_sort', 'ASC') */;
    }
	
	
	
	
	
	/**
     * Получить договоры к отделу.
     */
    public function contracts() {
		return $this->belongsToMany(
			Contract::class,
			'contract_department',
			'department_id',
			'contract_id',
			'id',
			'id')
			->using(ContractDepartment::class)
			->withPivot('show', 'hide');
    }
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function info() {
		return $this->hasOne(ContractDepartment::class, 'department_id', 'id');
	}
	
	
	
	
	
}