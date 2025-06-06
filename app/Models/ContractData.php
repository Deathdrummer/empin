<?php namespace App\Models;

use App\Models\Traits\Collectionable;
use App\Models\Traits\Dateable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractData extends Model {
    use HasFactory, Collectionable, Dateable;
	
	
	/**
     * Таблица
	 *
     * @var string
     */
	protected $table = 'contract_data';
	
	
	
	/**
     * Атрибуты, которые должны быть типизированы. (Конвертация полей при добавлении и получении)
	 *
     * @var array
     */
	protected $casts = [
        'type' 		=> 'integer',
        'from_id'	=> 'integer'
    ];
	
	
	
	/**
     * Поля разрешенные для редактирования
	 *
     * @var array
     */
	protected $fillable = [
		'contract_id',
		'department_id',
		'step_id',
		'type',
		'data',
		'from_id',
	];
	
	
	
	
	
	/**
     * Переопределение 
     *	- retrieved  полученное событие
     *	- saving  событие сохранения
     *	- saved  сохраненное событие
     *	- updating  событие обновления
     *	- updated  событие обновленной
     *	- creating  событие создания
     *	- created  созданное событие
     *	- replicating  реплицирующее событие
     *	- deleting  событие удаления модели
     *	- deleted  удаленное событие
     * @var array
     */
	public static function boot() {
    	parent::boot();
		
		self::creating(function ($model) {
			$userId = auth('site')->user()->id;
			//logger('creating '.$userId);
			//logger($model->toArray());
		});
		
		self::updating(function ($model) {
			//logger('updating '.$userId);
			//logger($model->toArray());
		});
	}
	
	
	
	
	
	
	/**
     * Информация 
     */
	public function meta() {
		return $this->belongsTo(Contract::class, 'contract_id', 'id');
	}
	
	
	
}