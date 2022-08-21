<?php namespace App\Models;

use App\Models\Traits\Collectionable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractData extends Model {
    use HasFactory, Collectionable;
	
	
	
	
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
}