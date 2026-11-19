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
	
	
	
	
	
	
	
	public function team():BelongsTo {
    	return $this->belongsTo(TimesheetTeam::class, 'team_id');
	}

	public function contract():BelongsTo {
    	return $this->belongsTo(Contract::class, 'contract_id')
			->select(['id', 'object_number', 'title', 'titul']);
	}
	
	
	
	
	/**
     * Получить этапы к отделу.
     */
    /** Создатель задания — его хозяин (П-ЗД-09); NULL у записей прототипа и сайта */
    public function creator():BelongsTo {
        return $this->belongsTo(Staff::class, 'creator_staff_id');
    }

    /** Виды работ задания из плана объекта с количествами (П-ЗД-11) */
    public function works():HasMany {
        return $this->hasMany(TimesheetContractWork::class, 'timesheet_contract_id');
    }

    /** Приглашения в задание, в том числе отозванные — история (П-ЗД-24) */
    public function invites():HasMany {
        return $this->hasMany(TimesheetContractInvite::class, 'timesheet_contract_id');
    }

    public function activeInvites():HasMany {
        return $this->invites()->whereNull('revoked_at');
    }

    /** Отчёт по заданию — не больше одного (П-ФК-02) */
    public function report():HasOne {
        return $this->hasOne(TaskReport::class, 'task_id');
    }

    public function chat():HasMany {
        return $this->hasMany(TimesheetChat::class, 'timesheet_contract_id', 'id');
    }
	
	
	
	
	
}