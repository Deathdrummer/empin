<?php namespace App\Models;

use App\Models\Traits\Collectionable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractSelection extends Model {
    use HasFactory, Collectionable;
	
	/**
     * Таблица
	 *
     * @var string
     */
	protected $table = 'contracts_selections';
	
	
	
	/**
     * Поля разрешенные для редактирования
	 *
     * @var array
     */
	protected $fillable = [
		'account_id',
		'title',
		'_sort',
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
	protected $casts = [
        'account_id' => 'integer',
    ];
	
	
	
	
	
	/**
     * Договоры подборки.
     */
	public function contracts() {
		return $this->belongsToMany(
			Contract::class,
			'contract_selection_contract',
			'selection_id',
			'contract_id',
			'id',
			'id');
	}
	
}