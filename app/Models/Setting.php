<?php namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model {
    use HasFactory;
	
	/**
     * Таблица
	 *
     * @var string
     */
	protected $table = 'settings';
	
	
	/**
     * Первичный ключ
	 *
     * @var string
     */
	protected $primaryKey = 'key';
	
	/**
     * Тип первичного ключа
	 *
     * @var string
     */
	protected $keyType = 'string';
	
	
	/**
     * Автоинкремент
	 *
     * @var string
     */
	public $incrementing = false;
	
	
	/**
     * учитывать временные поля created_at и updated_at
	 *
     * @var string
     */
	public $timestamps = false;
	
	
	
	/**
     * Поля разрешенные для редактирования
	 *
     * @var array
     */
	protected $fillable = [
		'key',
		'value',
		'group'
	];
	
	
	/**
     * Атрибуты, которые должны быть типизированы. (Конвертация полей при добавлении и получении)
     *
     * @var array
     */
    //protected $casts = [
    //    'value' => 'array',
    //];
	
	
	
	
	/**
	 * @param string  $value
	 * @return 
	 */
	public function setValueAttribute($value) {
		$this->attributes['value'] = is_array($value) ? json_encode($value) : $value;
	}
	
	
	/**
     * 
     *
     * @param string  $value
     * @return string
     */
    public function getValueAttribute($value) {
		return isJson($value) ? json_decode($value, true) : $value;
	}
	
	
}