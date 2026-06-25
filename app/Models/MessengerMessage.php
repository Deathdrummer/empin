<?php namespace App\Models;

use App\Models\Traits\Collectionable;
use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessengerMessage extends Model {
    use HasFactory, Filterable, Collectionable;



	/**
     * Таблица
	 *
     * @var string
     */
	protected $table = 'messenger_messages';


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
		'reactions' => 'array',
		'media' => 'array',
	];






	public function chat(): BelongsTo {
    	return $this->belongsTo(MessengerChat::class, 'chat_id');
	}

	public function profile(): BelongsTo {
    	return $this->belongsTo(Staff::class, 'from_id')
			->select(['id', 'sname', 'fname', 'mname']);
	}

	public function replyTo(): BelongsTo {
    	return $this->belongsTo(MessengerMessage::class, 'reply_to_id');
	}

	public function replies(): HasMany {
    	return $this->hasMany(MessengerMessage::class, 'reply_to_id');
	}

}
