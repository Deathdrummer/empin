<?php namespace App\Models;

use App\Models\Traits\Collectionable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Selection extends Model {
    use HasFactory, Collectionable;
	
	
	/**
     * Таблица
	 *
     * @var string
     */
	protected $table = 'contracts_selections';
	
	
	/**
     * учитывать временные поля created_at и updated_at
	 *
     * @var string
     */
	public $timestamps = false;
	
	
	/**
	* Атрибуты, для которых НЕ разрешено массовое присвоение значений.
	*
	* @var array
	*/
	protected $guarded = [];
	
	
	
	/**
     * Поля разрешенные для редактирования
	 *
     * @var array
     */
	protected $fillable = [
		'account_id',
		'title',
		'_sort'
	];
	
	
	
	
	/**
     * Атрибуты, которые должны быть типизированы. (Конвертация полей при добавлении и получении)
	 *
     * @var array
     */
	protected $casts = [
        'subscribed' => 'array',
    ];
	
	
	
	
	/**
     * Установка значений в момент создания модели
     *
     * @var array
     */
	public static function boot() {
    	parent::boot();
		self::creating(function ($model) {
			$model->account_id = auth('site')->user()->id;
		});
	}
	
	
	
	
	
	
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
	
	
	
	
	
	
	
	
	
	
	
	/**
     * Список подборок для выпадающего списка при присоединении договора к подборке
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeToChoose($query) {
		$userId = auth('site')->user()->id;
        $query->where('account_id', $userId)
			->orWhereJsonContains('subscribed', ['write' => $userId])
			->orderBy('_sort', 'ASC');
    }
	
	
}