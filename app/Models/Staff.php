<?php namespace App\Models;

use App\Models\Traits\Collectionable;
use App\Models\Traits\Dateable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class Staff extends Model {
    use HasFactory, Notifiable, HasRoles, Collectionable, Dateable;
	
	/**
     * Таблица
	 *
     * @var string
     */
	protected $table = 'staff';
	
	
	/**
     * Раздел аутентификации
	 *
     * @var string
     */
	protected $guard = 'admin';
	
	
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
	public $timestamps = true;
	
	
	protected $appends = ['full_name', 'department_id'];
	
	
	
	/**
     * Атрибуты, которые должны быть типизированы. (Конвертация полей при добавлении и получении)
	 *
     * @var array
     */
	protected $casts = [
        /* 'title' => 'array',
        'page_title' => 'array', */
    ];
	
	
	
	
	
	
	public function registred() {
		return $this->hasOne(User::class, 'staff_id', 'id');
	}
	
	
	
	
	
	
	public function getFullNameAttribute() {
        return "{$this->sname} {$this->fname} {$this->mname}";
    }
	
	
	public function getDepartmentIdAttribute() {
        return optional($this->registred)->department_id;
    }
	
	
	
	
	
	/**
	 * @param string  $value
	 * @return 
	 */
	//public function setTitleAttribute($value) {
	//	$this->attributes['title'] = is_array($value) ? json_encode($value) : $value;
	//}
	
	/**
	 * @param string  $value
	 * @return 
	 */
	//public function setPageTitleAttribute($value) {
	//	$this->attributes['page_title'] = is_array($value) ? json_encode($value) : $value;
	//}
	
	
	
	/**
     * 
     *
     * @param string  $value
     * @return string
     */
   /*  public function getTitleAttribute($value) {
		$value = isJson($value) ? json_decode($value, true) : $value;
		$lang = App::currentLocale();
		return $value[$lang] ?? null;
	} */
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function scopeWorking($query, $stat = true) {
		return $query->where('working', $stat);
	}
	
	
}
