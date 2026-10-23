<?php namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessengerMessageResource;
use App\Http\Resources\ObjectCommentResource;
use App\Models\MessengerChat;
use App\Models\MessengerChatMember;
use App\Models\MessengerMessage;
use App\Models\MessengerPollVote;
use App\Models\MessengerSetting;
use App\Models\Staff;
use App\Models\TimesheetChat;
use App\Services\Messenger\ChatDirectory;
use App\Services\Messenger\MediaStorage;
use App\Services\Messenger\MessengerNotifier;
use App\Services\Messenger\ObjectChats;
use App\Services\Messenger\Presence;
use App\Services\Messenger\Reactions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Мессенджер мобильного приложения: личные чаты, группы и чаты объектов (комментарии табеля).
 * Старые версии приложения пользуются только личными чатами и полной историей без limit — это поведение сохранено.
 */
class MessengerApiController extends Controller {

    public function __construct(
        private ChatDirectory $directory,
        private ObjectChats $objects,
        private MessengerNotifier $notifier,
    ) {
    }

    /**
     * Список чатов: собеседник или группа/объект, последнее сообщение, непрочитанные, закрепление, «без звука»
     */
    public function getChats(Request $request): JsonResponse {
        return response()->json($this->directory->listFor($this->me($request)));
    }

    /**
     * Сведения о чате: «в сети», участники группы, закреплённые сообщения
     */
    public function chatInfo(Request $request): JsonResponse {
        $validated = $request->validate(['chat_id' => 'required|integer']);

        $chat = $this->chatFor($request, $validated['chat_id']);
        if (!$chat) {
            return $this->chatNotFound();
        }

        $me = $this->me($request);
        $this->objects->syncMembers($chat);
        $member = $chat->member($me);
        $canAssign = $this->canAssignObjects($request);

        // Состав группы: добавляет любой участник, исключает администратор.
        // Состав объекта: добавляет участник или тот, кто ставит объекты бригадам, убирает — только он.
        return response()->json($this->directory->infoFor($chat, $me) + [
            'can_write'          => !$chat->isObject() || $this->canComment($request),
            'is_member'          => $member !== null,
            'can_add_members'    => $chat->isGroup() || ($chat->isObject() && ($member !== null || $canAssign)),
            'can_remove_members' => ($chat->isGroup() && $member?->role === MessengerChatMember::ROLE_ADMIN)
                || ($chat->isObject() && $canAssign),
        ]);
    }

    /**
     * Настройки мессенджера сотрудника (одинаковые на всех его устройствах)
     */
    public function settings(Request $request): JsonResponse {
        return response()->json(['mute_objects' => MessengerSetting::muteObjects($this->me($request))]);
    }

    /**
     * «Чаты объектов без звука»: сразу для всех объектов, и новые объекты приходят без звука
     */
    public function updateSettings(Request $request): JsonResponse {
        $validated = $request->validate(['mute_objects' => 'required|boolean']);

        $me = $this->me($request);
        $this->objects->setMuteObjects($me, (bool) $validated['mute_objects']);

        // Другие устройства сотрудника обновят список
        $this->afterResponse(fn () => $this->notifier->broadcast([$me], 'chat.updated', ['settings' => true]));

        return response()->json([
            'mute_objects' => (bool) $validated['mute_objects'],
            'total_unread' => $this->directory->totalUnread($me),
        ]);
    }

    /**
     * Получить или создать личный чат между двумя сотрудниками
     */
    public function getOrCreateChat(Request $request) {
        $validated = $request->validate([
            'participant_id' => 'required|integer|exists:staff,id',
        ]);

        $me = $this->me($request);
        if ((int) $validated['participant_id'] === $me) {
            return response()->json(['error' => 'Нельзя создать чат с самим собой'], 422);
        }

        $chat = MessengerChat::getOrCreate($me, (int) $validated['participant_id']);

        return response()->json(['chat_id' => $chat->id]);
    }

    /**
     * Открыть чат объекта (договора) из поиска или табеля. Открытие не делает участником.
     */
    public function openObjectChat(Request $request): JsonResponse {
        $validated = $request->validate(['contract_id' => 'required|integer|exists:contracts,id']);

        $chat = MessengerChat::forObject((int) $validated['contract_id']);

        return response()->json(['chat_id' => $chat->id]);
    }

    /**
     * Комментарии объекта просмотрены в табеле — в «Чатах» участника они больше не непрочитанные
     */
    public function markObjectRead(Request $request): JsonResponse {
        $validated = $request->validate(['contract_id' => 'required|integer|exists:contracts,id']);

        $me = $this->me($request);
        $contractId = (int) $validated['contract_id'];
        $chat = MessengerChat::forObject($contractId);
        $lastId = $this->objects->lastCommentId($contractId);

        if ($lastId) {
            $chat->markReadBy($me, $lastId);
        }

        return response()->json([
            'chat_id'      => $chat->id,
            'total_unread' => $this->directory->totalUnread($me),
        ]);
    }

    /**
     * Сообщения чата.
     * Без limit — вся история (так работают старые версии приложения).
     * limit + before_id — страница истории перед сообщением, after_id — новые после него,
     * around_id — окно вокруг сообщения (переход из поиска или к закреплённому).
     */
    public function getMessages(Request $request) {
        $validated = $request->validate([
            'chat_id'   => 'required|integer',
            'limit'     => 'nullable|integer|min:1|max:100',
            'before_id' => 'nullable|integer',
            'after_id'  => 'nullable|integer',
            'around_id' => 'nullable|integer',
        ]);

        $chat = $this->chatFor($request, $validated['chat_id']);
        if (!$chat) {
            return $this->chatNotFound();
        }

        $me = $this->me($request);

        if ($chat->isObject()) {
            $query = $this->objects->commentsQuery((int) $chat->contract_id)
                ->with(['profile', 'replyTo.profile', 'timesheetContract.team']);
            [$messages, $hasMore, $hasMoreNewer] = $this->page($query, $validated);

            return response()->json([
                'messages'          => $messages->map(fn (TimesheetChat $comment) => ObjectCommentResource::forChat($comment, $chat->id)->resolve($request))->values(),
                'has_more'          => $hasMore,
                'has_more_newer'    => $hasMoreNewer,
                'peer_last_read_id' => null,
            ]);
        }

        $query = MessengerMessage::where('chat_id', $chat->id)->with(['profile.registred', 'replyTo.profile', 'votes']);
        [$messages, $hasMore, $hasMoreNewer] = $this->page($query, $validated);

        $response = [
            'messages'          => MessengerMessageResource::collection($messages),
            'has_more'          => $hasMore,
            'has_more_newer'    => $hasMoreNewer,
            'peer_last_read_id' => $chat->isDirect() ? $chat->lastReadIdFor($chat->otherParticipantId($me)) : null,
        ];

        // Группа: докуда дочитал каждый участник — для галочек и «Прочитали»
        if ($chat->isGroup()) {
            $response['members_read'] = MessengerChatMember::where('chat_id', $chat->id)
                ->where('staff_id', '!=', $me)
                ->pluck('last_read_id', 'staff_id')
                ->map(fn ($id) => $id !== null ? (int) $id : null);
        }

        return response()->json($response);
    }

    /**
     * Добавить сообщение: текст, вложения, опрос (в группе), упоминания (в группе).
     * В чате объекта сообщение становится комментарием табеля.
     */
    public function addMessage(Request $request) {
        $validated = $request->validate([
            'chat_id'           => 'required|integer',
            'message'           => 'nullable|string',
            'reply_to_id'       => 'nullable|integer',
            'media'             => 'nullable|array|max:20',
            'media.*'           => MediaStorage::RULES,
            'type'              => 'nullable|in:text,poll',
            'poll'              => 'nullable|array',
            'poll.question'     => 'required_if:type,poll|nullable|string|max:255',
            'poll.options'      => 'required_if:type,poll|nullable|array|min:2|max:10',
            'poll.options.*'    => 'string|max:100',
            'poll.multiple'     => 'nullable|boolean',
            'mention_ids'       => 'nullable|array',
            'mention_ids.*'     => 'integer',
        ]);

        $chat = $this->chatFor($request, $validated['chat_id']);
        if (!$chat) {
            return $this->chatNotFound();
        }

        if ($chat->isObject()) {
            return $this->addObjectComment($request, $chat, $validated);
        }

        $me = $this->me($request);
        $text = $validated['message'] ?? '';
        $isPoll = ($validated['type'] ?? 'text') === MessengerMessage::TYPE_POLL;
        $replyToId = $validated['reply_to_id'] ?? null;

        if ($isPoll) {
            if (!$chat->isGroup()) {
                return response()->json(['error' => 'Опрос можно создать только в группе'], 422);
            }
            $options = array_values(array_filter(array_map('trim', $validated['poll']['options'] ?? []), fn ($o) => $o !== ''));
            if (count($options) < 2 || trim((string) ($validated['poll']['question'] ?? '')) === '') {
                return response()->json(['error' => 'Нужны вопрос и хотя бы два варианта ответа'], 422);
            }
        } elseif (trim($text) === '' && !$request->hasFile('media')) {
            return response()->json(['error' => 'Необходимо указать текст сообщения или прикрепить медиа'], 422);
        }

        // Ответить можно только на сообщение из этого же чата
        if ($replyToId && !MessengerMessage::where('id', $replyToId)->where('chat_id', $chat->id)->exists()) {
            return response()->json(['error' => 'Сообщение для ответа не найдено'], 422);
        }

        // Упомянуть можно только участников группы
        $mentions = [];
        if ($chat->isGroup() && !empty($validated['mention_ids'])) {
            $mentions = array_values(array_intersect(
                array_map('intval', $validated['mention_ids']),
                array_filter($chat->memberIds(), fn (int $id) => $id !== $me)
            ));
        }

        $media = $isPoll ? [] : MediaStorage::store($request, 'messenger/messages');

        $messageRecord = MessengerMessage::create([
            'chat_id'     => $chat->id,
            'from_id'     => $me,
            'type'        => $isPoll ? MessengerMessage::TYPE_POLL : MessengerMessage::TYPE_TEXT,
            'message'     => $isPoll ? '' : $text,
            'reply_to_id' => $replyToId,
            'media'       => $media ?: null,
            'poll'        => $isPoll ? [
                'question' => trim($validated['poll']['question']),
                'options'  => $options,
                'multiple' => (bool) ($validated['poll']['multiple'] ?? false),
            ] : null,
            'mentions'    => $mentions ?: null,
        ]);

        $messageRecord->load(['profile.registred', 'replyTo.profile', 'votes']);

        // Отправитель видел свой чат до этого сообщения включительно
        $chat->markReadBy($me, $messageRecord->id);

        $resource = new MessengerMessageResource($messageRecord);
        $payload = array_merge($resource->resolve($request), ['self' => false]);
        $preview = $this->directory->previewOf($messageRecord, false);
        $sender = Staff::find($me);

        // После ответа клиенту: отправка не ждёт Pusher и Expo, а их сбой не ломает отправку
        app()->terminating(fn () => $this->notifier->newMessage($chat, $sender, $payload, $preview, $mentions));

        return $resource;
    }

    /**
     * Изменить текст сообщения (в чате объекта — комментария)
     */
    public function updateMessage(Request $request, $id) {
        if (!$request->user()->can('mobile-app-can-edit-comment:site')) {
            return response()->json(['error' => 'У вас нет прав для редактирования сообщений'], 403);
        }

        $validated = $request->validate([
            'message' => 'required|string',
            'chat_id' => 'nullable|integer',
        ]);

        $me = $this->me($request);
        [$chat, $record] = $this->findMessage($request, (int) $id, $validated['chat_id'] ?? null);

        if (!$record) {
            return response()->json(['error' => 'Message not found'], 404);
        }

        if ((int) $record->from_id !== $me || ($record->type ?? 'text') === MessengerMessage::TYPE_SYSTEM) {
            return response()->json(['error' => 'У вас нет прав для редактирования этого сообщения'], 403);
        }

        $record->message = $validated['message'];
        $record->save();

        $payload = $this->messagePayload($request, $chat, $record);
        $this->afterResponse(fn () => $this->notifier->broadcastToChat($chat, 'message.updated', [
            'chat_id' => $chat->id,
            'message' => $this->forRecipients($chat, $payload, $me),
        ], $me));

        return response()->json($payload);
    }

    /**
     * Удалить сообщение (в чате объекта — комментарий)
     */
    public function removeMessage(Request $request, $id) {
        if (!$request->user()->can('mobile-app-can-delete-comment:site')) {
            return response()->json(['error' => 'У вас нет прав для удаления сообщений'], 403);
        }

        $me = $this->me($request);
        [$chat, $record] = $this->findMessage($request, (int) $id, $request->input('chat_id'));

        if (!$record) {
            return response()->json(['success' => false], 404);
        }

        if ((int) $record->from_id !== $me || ($record->type ?? 'text') === MessengerMessage::TYPE_SYSTEM) {
            return response()->json(['error' => 'У вас нет прав для удаления этого сообщения'], 403);
        }

        MediaStorage::delete($record->media);
        $messageId = $record->id;
        $record->delete();

        $this->afterResponse(fn () => $this->notifier->broadcastToChat($chat, 'message.deleted', [
            'chat_id'    => $chat->id,
            'message_id' => $messageId,
        ], $me));

        return response()->json(['success' => true]);
    }

    /**
     * Добавить/убрать реакцию (повторное нажатие той же реакции убирает её)
     */
    public function toggleReaction(Request $request) {
        $validated = $request->validate([
            'message_id' => 'required|integer',
            'emoji'      => 'required|string|max:16',
            'chat_id'    => 'nullable|integer',
        ]);

        $me = $this->me($request);
        [$chat, $record] = $this->findMessage($request, (int) $validated['message_id'], $validated['chat_id'] ?? null);

        // Реакция — только в доступном чате; чужое сообщение выглядит как несуществующее
        if (!$record) {
            return response()->json(['error' => 'Message not found'], 404);
        }

        // Одна реакция от человека: та же снимается, другая заменяет прежнюю
        $reactions = Reactions::toggle($record->reactions ?? [], (int) $request->user()->id, $validated['emoji']);

        $record->reactions = $reactions;
        // Реакция — не правка текста: время изменения не трогаем, иначе у сообщения появится «изменено»
        $record->timestamps = false;
        $record->save();
        $record->timestamps = true;

        $payload = $this->messagePayload($request, $chat, $record);
        $this->afterResponse(fn () => $this->notifier->broadcastToChat($chat, 'message.updated', [
            'chat_id' => $chat->id,
            'message' => $this->forRecipients($chat, $payload, $me),
        ], $me));

        return response()->json([
            'success'   => true,
            'reactions' => $reactions,
        ]);
    }

    /**
     * Отметить сообщения чата прочитанными до message_id включительно.
     * Граница прочитанного только растёт; участники получают chat.read (галочки «прочитано»).
     */
    public function markRead(Request $request): JsonResponse {
        $validated = $request->validate([
            'chat_id'    => 'required|integer',
            'message_id' => 'required|integer',
        ]);

        $chat = $this->chatFor($request, $validated['chat_id']);
        if (!$chat) {
            return $this->chatNotFound();
        }

        $me = $this->me($request);

        // Не дальше последнего существующего сообщения этого чата
        $lastId = ($chat->isObject()
                ? $this->objects->commentsQuery((int) $chat->contract_id)
                : MessengerMessage::where('chat_id', $chat->id))
            ->where('id', '<=', $validated['message_id'])
            ->max('id');

        if ($lastId && $chat->markReadBy($me, (int) $lastId) && !$chat->isObject()) {
            $this->afterResponse(fn () => $this->notifier->broadcastToChat($chat, 'chat.read', [
                'chat_id'      => $chat->id,
                'reader_id'    => $me,
                'last_read_id' => (int) $lastId,
            ], $me));
        }

        return response()->json([
            'last_read_id' => $chat->lastReadIdFor($me),
            'total_unread' => $this->directory->totalUnread($me),
        ]);
    }

    /**
     * «Печатает…»: клиент вызывает не чаще раза в несколько секунд
     */
    public function typing(Request $request): JsonResponse {
        $validated = $request->validate(['chat_id' => 'required|integer']);

        $chat = $this->chatFor($request, $validated['chat_id']);
        if (!$chat) {
            return $this->chatNotFound();
        }

        $me = $this->me($request);
        $name = ChatDirectory::shortName(Staff::find($me));

        $this->afterResponse(fn () => $this->notifier->broadcastToChat($chat, 'chat.typing', [
            'chat_id'  => $chat->id,
            'staff_id' => $me,
            'name'     => $name,
        ], $me));

        return response()->json(['success' => true]);
    }

    /**
     * Закрепить чат вверху списка — на всех устройствах сотрудника
     */
    public function pinChat(Request $request): JsonResponse {
        return $this->updateMemberState($request, 'pinned');
    }

    /**
     * «Без звука»: без push и без учёта в значке непрочитанных
     */
    public function muteChat(Request $request): JsonResponse {
        return $this->updateMemberState($request, 'muted');
    }

    /**
     * Закрепить сообщение в чате — видно всем участникам
     */
    public function pinMessage(Request $request): JsonResponse {
        $validated = $request->validate([
            'chat_id'    => 'required|integer',
            'message_id' => 'required|integer',
            'pinned'     => 'required|boolean',
        ]);

        [$chat, $record] = $this->findMessage($request, (int) $validated['message_id'], (int) $validated['chat_id']);
        if (!$record || ($record->type ?? 'text') === MessengerMessage::TYPE_SYSTEM) {
            return response()->json(['error' => 'Message not found'], 404);
        }

        $record->pinned_at = $validated['pinned'] ? now() : null;
        $record->pinned_by = $validated['pinned'] ? $this->me($request) : null;
        $record->timestamps = false;
        $record->save();
        $record->timestamps = true;

        $pinned = $this->directory->pinnedMessages($chat);
        $this->afterResponse(fn () => $this->notifier->broadcastToChat($chat, 'chat.pinned', [
            'chat_id'         => $chat->id,
            'pinned_messages' => $pinned,
        ]));

        return response()->json(['pinned_messages' => $pinned]);
    }

    /**
     * Голос в опросе: options — выбранные варианты (пустой массив — отозвать голос)
     */
    public function votePoll(Request $request): JsonResponse {
        $validated = $request->validate([
            'message_id' => 'required|integer',
            'options'    => 'present|array|max:10',
            'options.*'  => 'integer|min:0|max:9',
        ]);

        $message = MessengerMessage::find($validated['message_id']);
        $me = $this->me($request);

        if (!$message || $message->type !== MessengerMessage::TYPE_POLL || !$message->chat?->canAccess($me)) {
            return response()->json(['error' => 'Опрос не найден'], 404);
        }

        $count = count($message->poll['options'] ?? []);
        $options = array_values(array_unique(array_filter($validated['options'], fn ($i) => $i < $count)));
        if (!($message->poll['multiple'] ?? false)) {
            $options = array_slice($options, 0, 1);
        }

        DB::transaction(function () use ($message, $me, $options) {
            MessengerPollVote::where('message_id', $message->id)->where('staff_id', $me)->delete();
            foreach ($options as $index) {
                MessengerPollVote::create(['message_id' => $message->id, 'staff_id' => $me, 'option_index' => $index]);
            }
        });

        $message->load(['profile.registred', 'replyTo.profile', 'votes']);
        $payload = array_merge((new MessengerMessageResource($message))->resolve($request), ['self' => false]);
        $chat = $message->chat;

        // Своим другим устройствам тоже — голоса видны везде
        $this->afterResponse(fn () => $this->notifier->broadcastToChat($chat, 'message.updated', [
            'chat_id' => $chat->id,
            'message' => $payload,
        ]));

        return response()->json(['message' => $payload]);
    }

    /**
     * Поиск: сообщения во всех чатах сотрудника (или в одном chat_id), группы и объекты по названию
     */
    public function search(Request $request): JsonResponse {
        $validated = $request->validate([
            'q'       => 'required|string|min:2|max:100',
            'chat_id' => 'nullable|integer',
        ]);

        $me = $this->me($request);
        $like = '%' . addcslashes(trim($validated['q']), '%_\\') . '%';
        $chat = null;

        if (!empty($validated['chat_id'])) {
            $chat = $this->chatFor($request, $validated['chat_id']);
            if (!$chat) {
                return $this->chatNotFound();
            }
        }

        $messages = collect();

        if (!$chat || !$chat->isObject()) {
            $messages = MessengerMessage::query()
                ->select('messenger_messages.*')
                ->join('messenger_chat_members as m', function ($join) use ($me) {
                    $join->on('m.chat_id', '=', 'messenger_messages.chat_id')->where('m.staff_id', $me);
                })
                ->when($chat, fn ($q) => $q->where('messenger_messages.chat_id', $chat->id))
                ->where('messenger_messages.type', MessengerMessage::TYPE_TEXT)
                ->where('messenger_messages.message', 'like', $like)
                ->orderByDesc('messenger_messages.id')
                ->limit(30)
                ->with(['profile', 'chat.contract'])
                ->get()
                ->map(fn (MessengerMessage $message) => $this->searchHit($message->chat, $message, $me));
        }

        if (!$chat || $chat->isObject()) {
            $comments = TimesheetChat::query()
                ->select('timesheet_chat.*', 'c.id as messenger_chat_id')
                ->join('timesheet_contracts as tc', 'tc.id', '=', 'timesheet_chat.timesheet_contract_id')
                ->join('messenger_chats as c', function ($join) {
                    $join->on('c.contract_id', '=', 'tc.contract_id')->where('c.type', MessengerChat::TYPE_OBJECT);
                })
                ->when($chat, fn ($q) => $q->where('c.id', $chat->id), fn ($q) => $q->whereIn('c.id', MessengerChatMember::where('staff_id', $me)->select('chat_id')))
                ->where('timesheet_chat.message', 'like', $like)
                ->orderByDesc('timesheet_chat.id')
                ->limit(30)
                ->with('profile')
                ->get();

            $objectChats = MessengerChat::whereIn('id', $comments->pluck('messenger_chat_id'))->with('contract')->get()->keyBy('id');
            $messages = $messages->concat($comments->map(
                fn (TimesheetChat $comment) => $this->searchHit($objectChats[$comment->messenger_chat_id], $comment, $me)
            ));
        }

        $chats = [];
        if (!$chat) {
            $groups = MessengerChat::forStaff($me)
                ->where('type', MessengerChat::TYPE_GROUP)
                ->where('title', 'like', $like)
                ->limit(10)
                ->get()
                ->map(fn (MessengerChat $group) => ['type' => 'group', 'chat_id' => $group->id, 'title' => $group->title]);

            $objects = DB::table('contracts')
                ->where(fn ($q) => $q->where('object_number', 'like', $like)->orWhere('title', 'like', $like))
                ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('timesheet_contracts')->whereColumn('timesheet_contracts.contract_id', 'contracts.id'))
                ->orderByDesc('id')
                ->limit(10)
                ->get(['id', 'object_number', 'title'])
                ->map(fn ($object) => [
                    'type'          => 'object',
                    'contract_id'   => (int) $object->id,
                    'object_number' => $object->object_number,
                    'title'         => trim($object->object_number . ' ' . $object->title),
                ]);

            $chats = $groups->concat($objects)->values()->all();
        }

        return response()->json([
            'chats'    => $chats,
            'messages' => $messages->sortByDesc('created_at')->take(40)->values()->all(),
        ]);
    }

    /**
     * Приложение открыто: отметка «в сети» (раз в минуту)
     */
    public function presence(Request $request): JsonResponse {
        Presence::touch($this->me($request));

        return response()->json(['success' => true]);
    }

    // --- Вспомогательное ---

    private function me(Request $request): int {
        return (int) $request->user()->staff_id;
    }

    private function canComment(Request $request): bool {
        return $request->user()->can('mobile-app-can-create-comment:site');
    }

    /** Ставит объекты бригадам в табеле — управляет составом чатов объектов */
    private function canAssignObjects(Request $request): bool {
        return $request->user()->can('mobile-app-can-create-contract:site');
    }

    /**
     * Чат, если текущий сотрудник может его открыть; иначе null
     */
    private function chatFor(Request $request, $chatId): ?MessengerChat {
        $chat = MessengerChat::find($chatId);

        return $chat && $chat->canAccess($this->me($request)) ? $chat : null;
    }

    /**
     * Чужой чат отвечает так же, как несуществующий — не раскрываем, что он есть
     */
    private function chatNotFound(): JsonResponse {
        return response()->json(['error' => 'Чат не найден'], 404);
    }

    /**
     * Сообщение доступного чата. Без chat_id — сообщение мессенджера (так работают старые версии);
     * с chat_id чата объекта — комментарий табеля этого объекта.
     *
     * @return array{0: ?MessengerChat, 1: MessengerMessage|TimesheetChat|null}
     */
    private function findMessage(Request $request, int $messageId, $chatId): array {
        if ($chatId) {
            $chat = $this->chatFor($request, $chatId);
            if (!$chat) {
                return [null, null];
            }

            $record = $chat->isObject()
                ? $this->objects->commentsQuery((int) $chat->contract_id)->whereKey($messageId)->first()
                : MessengerMessage::where('chat_id', $chat->id)->whereKey($messageId)->first();

            return [$chat, $record];
        }

        $record = MessengerMessage::find($messageId);
        $chat = $record?->chat;

        return $chat && $chat->canAccess($this->me($request)) ? [$chat, $record] : [null, null];
    }

    private function messagePayload(Request $request, MessengerChat $chat, $record): array {
        if ($chat->isObject()) {
            $record->load(['profile', 'replyTo.profile', 'timesheetContract.team']);

            return ObjectCommentResource::forChat($record, $chat->id)->resolve($request);
        }

        $record->load(['profile.registred', 'replyTo.profile', 'votes']);

        return (new MessengerMessageResource($record))->resolve($request);
    }

    /**
     * Сообщение в событии: «своё» — с точки зрения получателя. В личном чате получатель один —
     * собеседник; в группе и объекте получателей много, и «своё» клиент определяет по from_id.
     */
    private function forRecipients(MessengerChat $chat, array $payload, int $me): array {
        $self = $chat->isDirect() && (int) $payload['from_id'] === $chat->otherParticipantId($me);

        return array_merge($payload, ['self' => $self]);
    }

    private function afterResponse(callable $callback): void {
        app()->terminating($callback);
    }

    private function updateMemberState(Request $request, string $field): JsonResponse {
        $validated = $request->validate([
            'chat_id' => 'required|integer',
            $field    => 'required|boolean',
        ]);

        $chat = $this->chatFor($request, $validated['chat_id']);
        if (!$chat) {
            return $this->chatNotFound();
        }

        $me = $this->me($request);
        // Закрепить или выключить звук у чата объекта — значит следить за ним: сотрудник становится участником
        $member = $chat->isObject() ? $this->objects->addMember($chat, $me, false) : $chat->ensureMember($me);
        $member->{$field === 'pinned' ? 'pinned_at' : 'muted'} = $field === 'pinned'
            ? ($validated['pinned'] ? now() : null)
            : (bool) $validated['muted'];
        $member->save();

        // Другие устройства сотрудника обновят список
        $this->afterResponse(fn () => $this->notifier->broadcast([$me], 'chat.updated', ['chat_id' => $chat->id]));

        return response()->json([
            'chat_id'      => $chat->id,
            'pinned'       => $member->pinned_at !== null,
            'muted'        => (bool) $member->muted,
            'total_unread' => $this->directory->totalUnread($me),
        ]);
    }

    /**
     * Комментарий табеля из чата объекта: попадает в объект на ближайший к сегодня день
     */
    private function addObjectComment(Request $request, MessengerChat $chat, array $validated) {
        if (!$this->canComment($request)) {
            return response()->json(['error' => 'У вас нет прав для комментариев к объектам'], 403);
        }

        $text = $validated['message'] ?? '';
        if (trim($text) === '' && !$request->hasFile('media')) {
            return response()->json(['error' => 'Необходимо указать текст сообщения или прикрепить медиа'], 422);
        }

        $contractId = (int) $chat->contract_id;
        $target = $this->objects->targetTimesheetContract($contractId);
        if (!$target) {
            return response()->json(['error' => 'Объекта нет в табеле'], 422);
        }

        $replyToId = $validated['reply_to_id'] ?? null;
        if ($replyToId && !$this->objects->commentsQuery($contractId)->whereKey($replyToId)->exists()) {
            return response()->json(['error' => 'Сообщение для ответа не найдено'], 422);
        }

        $me = $this->me($request);
        $media = MediaStorage::store($request, 'timesheet/comments');

        $comment = $target->chat()->create([
            'from_id'     => $me,
            'message'     => $text,
            'reply_to_id' => $replyToId,
            'media'       => $media ?: null,
        ]);

        // Написавший в чат объекта становится его участником — увидит ответы в «Чатах»
        $this->objects->addMember($chat, $me, false);
        $chat->markReadBy($me, $comment->id);

        $payload = $this->messagePayload($request, $chat, $comment);
        $preview = $this->directory->previewOf($comment, true);
        $sender = Staff::find($me);
        $event = array_merge($payload, ['self' => false]);

        $this->afterResponse(fn () => $this->notifier->newMessage($chat, $sender, $event, $preview));

        return response()->json($payload, 201);
    }

    /**
     * Найденное сообщение для списка результатов поиска
     */
    private function searchHit(MessengerChat $chat, $message, int $me): array {
        $peer = $chat->isDirect() ? Staff::find($chat->otherParticipantId($me), ['id', 'sname', 'fname']) : null;

        return [
            'chat_id'    => $chat->id,
            'chat_type'  => $chat->type ?? MessengerChat::TYPE_DIRECT,
            'chat_title' => $this->directory->titleOf($chat, $peer),
            'peer_id'    => $peer?->id,
            'message_id' => $message->id,
            'from_name'  => (int) $message->from_id === $me ? 'Вы' : ChatDirectory::shortName($message->profile),
            'text'       => mb_strimwidth(trim(preg_replace('/\s+/u', ' ', (string) $message->message)), 0, 160, '…'),
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }

    /**
     * Страница сообщений по параметрам limit / before_id / after_id / around_id
     *
     * @return array{0: Collection, 1: bool, 2: bool} сообщения по возрастанию, есть ли старше, есть ли новее
     */
    private function page($query, array $params): array {
        $limit = $params['limit'] ?? null;

        if (!empty($params['around_id'])) {
            $half = max(1, intdiv($limit ?? 40, 2));
            $older = (clone $query)->where('id', '<=', $params['around_id'])->orderByDesc('id')->limit($half + 1)->get();
            $newer = (clone $query)->where('id', '>', $params['around_id'])->orderBy('id')->limit($half + 1)->get();

            return [
                $older->take($half)->reverse()->values()->concat($newer->take($half))->values(),
                $older->count() > $half,
                $newer->count() > $half,
            ];
        }

        if (!empty($params['after_id'])) {
            $count = $limit ?? 100;
            $rows = (clone $query)->where('id', '>', $params['after_id'])->orderBy('id')->limit($count + 1)->get();

            return [$rows->take($count)->values(), false, $rows->count() > $count];
        }

        if ($limit) {
            if (!empty($params['before_id'])) {
                $query->where('id', '<', $params['before_id']);
            }
            $rows = $query->orderByDesc('id')->limit($limit + 1)->get();

            return [$rows->take($limit)->reverse()->values(), $rows->count() > $limit, false];
        }

        return [$query->orderBy('created_at')->orderBy('id')->get(), false, false];
    }
}
