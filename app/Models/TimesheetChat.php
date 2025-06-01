<?php namespace App\Models;

use App\Models\Traits\Collectionable;
use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimesheetChat extends Model {
    use HasFactory, Filterable, Collectionable;
	
	
	
	/**
     * Таблица
	 *
     * @var string
     */
	protected $table = 'timesheet_chat';
	
	
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
	protected $guarded = [];
	
	
	/**
     * учитывать временные поля created_at и updated_at
	 *
     * @var string
     */
	public $timestamps = true;
	
	
	/**
     * Аксессоры
	 *
     * @var array
     */
	protected $appends = [];
	
	
	
	/**
     * Атрибуты, которые должны быть типизированы. (Конвертация полей при добавлении и получении)
	 *
     * @var array
     */
	protected $casts = [];
	
	
	
	
	
	
	public function profile():BelongsTo {
    	return $this->belongsTo(Staff::class, 'from_id')
			->select(['id', 'sname', 'fname', 'mname']);
	}
	
}