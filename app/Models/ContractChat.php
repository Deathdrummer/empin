<?php namespace App\Models;

use App\Models\Traits\Collectionable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractChat extends Model {
    use HasFactory, Collectionable;
	
	
	/**
     * Таблица
	 *
     * @var string
     */
	protected $table = 'contracts_chats';
	
	
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
     * Получить аккаунт, связанный с сообщением.
     */
    public function user() {
        return $this->hasOne(User::class, 'id', 'account_id');
    }
	
}