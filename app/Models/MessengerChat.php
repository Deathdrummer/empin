<?php namespace App\Models;

use App\Models\Traits\Collectionable;
use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessengerChat extends Model {
    use HasFactory, Filterable, Collectionable;



	/**
     * Таблица
	 *
     * @var string
     */
	protected $table = 'messenger_chats';


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






	public function messages(): HasMany {
    	return $this->hasMany(MessengerMessage::class, 'chat_id');
	}

	public function participant1(): BelongsTo {
    	return $this->belongsTo(Staff::class, 'participant_1_id');
	}

	public function participant2(): BelongsTo {
    	return $this->belongsTo(Staff::class, 'participant_2_id');
	}

	/**
	 * Получить или создать чат между двумя пользователями
	 *
	 * @param int $userId1
	 * @param int $userId2
	 * @return MessengerChat
	 */
	public static function getOrCreate(int $userId1, int $userId2): MessengerChat {
		// Нормализуем порядок участников (меньший ID в participant_1)
		$participant1Id = min($userId1, $userId2);
		$participant2Id = max($userId1, $userId2);

		return static::firstOrCreate([
			'participant_1_id' => $participant1Id,
			'participant_2_id' => $participant2Id,
		]);
	}

}
