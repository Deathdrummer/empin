<?php namespace App\Models;

use App\Models\Traits\Collectionable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteParserSubject extends Model {
	use HasFactory, Collectionable;
	
	/**
     * Таблица
	 *
     * @var string
     */
	protected $table = 'parsed_sites_subjects';
	
	
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
	public $timestamps = false;
	
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function contacts() {
		return $this->hasMany(SiteParser::class, 'subject_id', 'id');
	}
	
	
}