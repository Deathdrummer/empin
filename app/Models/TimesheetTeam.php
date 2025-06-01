<?php namespace App\Models;

use App\Helpers\DdrDateTime;
use App\Models\Traits\Collectionable;
use App\Models\Traits\Filterable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimesheetTeam extends Model {
    use HasFactory, Filterable, Collectionable;
	
	/**
     * Таблица
	 *
     * @var string
     */
	protected $table = 'timesheet_teams';
	
	
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
	protected $casts = [
		//'day' => 'date',
	];
	
	
	
	
	
	
	

    public function contracts():HasMany {
        return $this->hasMany(TimesheetContract::class, 'team_id', 'id')/* ->orderBy('_sort', 'ASC') */;
    }
	
	
	
	

    public function profile():BelongsTo {
        return $this->belongsTo(Staff::class, 'staff_id', 'id')
			->select(['id', 'sname', 'fname', 'mname']);
    }
	
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function getDayAtAttribute($value) {
		return $value ? Carbon::parse($value)->toDateString() : null;
	}
	
	
	
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function scopeDay($query, $date = null) {
		if (!$date) return $query;
		return $query->where('day', $date);
	}
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function scopeGetByDaysIndexes($query, $daysOffsets = null) {
		if (!is_array($daysOffsets)) $daysOffsets = [$daysOffsets];
		
		$dates = [];
		foreach ($daysOffsets as $idx) {
			$dates[] = DdrDateTime::getOffsetDate($idx)->toDateString();
		}
		
		if (!$dates) return $query;
		return $query->whereIn('day', $dates);
	}
	
	
	
	
}