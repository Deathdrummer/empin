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



	/**
	 * Чаты, в которых участвует сотрудник
	 */
	public function scopeForStaff($query, int $staffId) {
		return $query->where(function ($q) use ($staffId) {
			$q->where('participant_1_id', $staffId)->orWhere('participant_2_id', $staffId);
		});
	}

	public function isParticipant(?int $staffId): bool {
		return $staffId !== null
			&& ((int) $this->participant_1_id === $staffId || (int) $this->participant_2_id === $staffId);
	}

	public function otherParticipantId(int $staffId): int {
		return (int) $this->participant_1_id === $staffId
			? (int) $this->participant_2_id
			: (int) $this->participant_1_id;
	}

	/**
	 * Последнее прочитанное сотрудником сообщение (null — ничего не прочитано)
	 */
	public function lastReadIdFor(int $staffId): ?int {
		$value = (int) $this->participant_1_id === $staffId
			? $this->participant_1_last_read_id
			: $this->participant_2_last_read_id;

		return $value !== null ? (int) $value : null;
	}

	/**
	 * Сдвинуть границу прочитанного вперёд (назад она не двигается).
	 * Возвращает true, если граница изменилась.
	 */
	public function markReadBy(int $staffId, int $messageId): bool {
		$column = (int) $this->participant_1_id === $staffId
			? 'participant_1_last_read_id'
			: 'participant_2_last_read_id';

		$updated = static::whereKey($this->id)
			->where(function ($q) use ($column, $messageId) {
				$q->whereNull($column)->orWhere($column, '<', $messageId);
			})
			->update([$column => $messageId]);

		if ($updated) {
			$this->{$column} = $messageId;
		}

		return $updated > 0;
	}

}
