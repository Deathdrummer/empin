<?php namespace App\Models;

use App\Models\Traits\Collectionable;
use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class MessengerChat extends Model {
    use HasFactory, Filterable, Collectionable;

	public const TYPE_DIRECT = 'direct';
	public const TYPE_GROUP = 'group';
	// Чат объекта: комментарии табеля к договору за все дни
	public const TYPE_OBJECT = 'object';
	// «Избранное»: сообщения себе (заметки, пересланное на потом), единственный участник — владелец
	public const TYPE_SAVED = 'saved';



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
	protected $casts = [
		'contract_id' => 'integer',
		'created_by' => 'integer',
	];






	public function messages(): HasMany {
    	return $this->hasMany(MessengerMessage::class, 'chat_id');
	}

	public function members(): HasMany {
		return $this->hasMany(MessengerChatMember::class, 'chat_id');
	}

	public function participant1(): BelongsTo {
    	return $this->belongsTo(Staff::class, 'participant_1_id');
	}

	public function participant2(): BelongsTo {
    	return $this->belongsTo(Staff::class, 'participant_2_id');
	}

	public function contract(): BelongsTo {
		return $this->belongsTo(Contract::class, 'contract_id')->select(['id', 'object_number', 'title']);
	}

	public function isDirect(): bool {
		return ($this->type ?? self::TYPE_DIRECT) === self::TYPE_DIRECT;
	}

	public function isGroup(): bool {
		return $this->type === self::TYPE_GROUP;
	}

	public function isObject(): bool {
		return $this->type === self::TYPE_OBJECT;
	}

	public function isSaved(): bool {
		return $this->type === self::TYPE_SAVED;
	}

	/**
	 * «Избранное» сотрудника: создаётся при первом обращении. Блокировка строки сотрудника — чтобы два
	 * одновременных запроса (две вкладки, повтор) не создали два «Избранных»
	 */
	public static function savedFor(int $staffId): MessengerChat {
		$find = fn () => static::where('type', self::TYPE_SAVED)->where('created_by', $staffId)->first();

		return $find() ?? DB::transaction(function () use ($staffId, $find) {
			Staff::whereKey($staffId)->lockForUpdate()->first();
			$chat = $find() ?? static::create(['type' => self::TYPE_SAVED, 'created_by' => $staffId]);
			$chat->ensureMember($staffId);

			return $chat;
		});
	}

	/**
	 * Получить или создать личный чат между двумя сотрудниками
	 *
	 * @param int $userId1
	 * @param int $userId2
	 * @return MessengerChat
	 */
	public static function getOrCreate(int $userId1, int $userId2): MessengerChat {
		// Нормализуем порядок участников (меньший ID в participant_1)
		$participant1Id = min($userId1, $userId2);
		$participant2Id = max($userId1, $userId2);

		$chat = static::firstOrCreate([
			'participant_1_id' => $participant1Id,
			'participant_2_id' => $participant2Id,
		], [
			'type' => self::TYPE_DIRECT,
		]);

		$chat->ensureMember($participant1Id);
		$chat->ensureMember($participant2Id);

		return $chat;
	}

	/**
	 * Чат объекта (договора): создаётся при первом обращении
	 */
	public static function forObject(int $contractId): MessengerChat {
		$attributes = ['type' => self::TYPE_OBJECT, 'contract_id' => $contractId];

		try {
			return static::firstOrCreate(['contract_id' => $contractId], $attributes);
		} catch (QueryException $error) {
			// Два запроса одновременно создали чат одного объекта — берём созданный
			return static::where('contract_id', $contractId)->firstOrFail();
		}
	}

	/**
	 * Чаты, в которых сотрудник участвует
	 */
	public function scopeForStaff($query, int $staffId) {
		return $query->whereIn('messenger_chats.id', MessengerChatMember::where('staff_id', $staffId)->select('chat_id'));
	}

	/**
	 * Может ли сотрудник читать и писать в чат.
	 * Личный — двое собеседников, группа — её участники, чат объекта — любой сотрудник (как комментарии в табеле).
	 */
	public function canAccess(?int $staffId): bool {
		if ($staffId === null) {
			return false;
		}

		if ($this->isObject()) {
			return true;
		}

		if ($this->isDirect()) {
			return (int) $this->participant_1_id === $staffId || (int) $this->participant_2_id === $staffId;
		}

		return $this->member($staffId) !== null;
	}

	public function isParticipant(?int $staffId): bool {
		return $this->canAccess($staffId);
	}

	public function otherParticipantId(int $staffId): int {
		return (int) $this->participant_1_id === $staffId
			? (int) $this->participant_2_id
			: (int) $this->participant_1_id;
	}

	public function member(int $staffId): ?MessengerChatMember {
		return MessengerChatMember::where('chat_id', $this->id)->where('staff_id', $staffId)->first();
	}

	/**
	 * Участник (для объекта — отслеживающий). Новый отслеживающий объект не получает
	 * старые комментарии непрочитанными: граница прочитанного — $lastReadId.
	 */
	public function ensureMember(int $staffId, ?int $lastReadId = null, string $role = MessengerChatMember::ROLE_MEMBER): MessengerChatMember {
		return MessengerChatMember::firstOrCreate(
			['chat_id' => $this->id, 'staff_id' => $staffId],
			['last_read_id' => $lastReadId, 'role' => $role]
		);
	}

	/**
	 * Сотрудники чата, которым уходят события реального времени и push
	 *
	 * @return int[]
	 */
	public function memberIds(): array {
		return MessengerChatMember::where('chat_id', $this->id)->pluck('staff_id')->map(fn ($id) => (int) $id)->all();
	}

	/**
	 * Последнее прочитанное сотрудником сообщение (null — ничего не прочитано)
	 */
	public function lastReadIdFor(int $staffId): ?int {
		return $this->member($staffId)?->last_read_id;
	}

	/**
	 * Сдвинуть границу прочитанного вперёд (назад она не двигается).
	 * Возвращает true, если граница изменилась. Чтение чата объекта не делает участником —
	 * их определяет табель и ручное добавление.
	 */
	public function markReadBy(int $staffId, int $messageId): bool {
		if (!$this->isObject()) {
			$this->ensureMember($staffId);
		}

		return MessengerChatMember::where('chat_id', $this->id)
			->where('staff_id', $staffId)
			->where(function ($q) use ($messageId) {
				$q->whereNull('last_read_id')->orWhere('last_read_id', '<', $messageId);
			})
			->update(['last_read_id' => $messageId]) > 0;
	}

}
