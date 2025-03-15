<?php namespace App\Models;

use App\Models\Traits\Collectionable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListUser extends Model {
     use HasFactory, Collectionable;
	
	/**
     * Таблица
	 *
     * @var string
     */
	protected $table = 'list_user';
	
	
	/**
     * Поля разрешенные для редактирования
	 *
     * @var array
     */
	protected $fillable = [];
	
	
	/**
     * Поля разрешенные для редактирования
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
     * Атрибуты, которые должны быть типизированы. (Конвертация полей при добавлении и получении)
	 *
     * @var array
     */
	protected $casts = [
		'user_id' => 'integer',
		'list_id' => 'integer',
	];
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function scopeLists($query, $staffId = null) {
		if (!$staffId) return $query;
		return $query->select('list_id')->where('staff_id', $staffId);
	}
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function scopeStaff($query, $listId = null) {
		if (!$listId) return $query;
		return $query->select('staff_id')->where('list_id', $listId);
	}
	
	
	
	
	
	// В модели ListUser
	public static function getStaffLists() {
		return self::all()
			->groupBy('staff_id')
			->map(fn($group) => $group->pluck('list_id')->toArray());
	}
	

}
