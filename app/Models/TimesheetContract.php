<?php namespace App\Models;

use App\Models\Traits\Collectionable;
use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TimesheetContract extends Model {
    use HasFactory, Filterable, Collectionable;
	
	
	
	
	/**
     * Таблица
	 *
     * @var string
     */
	protected $table = 'timesheet_contracts';
	
	
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
	
	
	
	
	
	
	
	public function contract():BelongsTo {
    	return $this->belongsTo(Contract::class, 'contract_id')
			->select(['id', 'object_number', 'title', 'titul']);
	}
	
	
	
	
	/**
     * Получить этапы к отделу.
     */
    public function chat():HasMany {
        return $this->hasMany(TimesheetChat::class, 'timesheet_contract_id', 'id');
    }
	
	
	
	
	
}