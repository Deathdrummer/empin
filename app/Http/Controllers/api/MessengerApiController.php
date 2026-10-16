<?php namespace App\Http\Controllers\api;

use App\Events\MessengerEvent;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessengerMessageResource;
use App\Models\MessengerChat;
use App\Models\MessengerMessage;
use App\Models\Staff;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MessengerApiController extends Controller {

    // Pusher принимает событие до 10 КБ; длинное сообщение клиент дозагрузит сам
    private const MAX_EVENT_BYTES = 9000;

    public function __construct(private PushNotificationService $pushService) {
    }

    /**
     * Список чатов текущего сотрудника: собеседник, последнее сообщение, непрочитанные.
     * Чаты без сообщений не показываются. Сортировка — по последнему сообщению.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getChats(Request $request): JsonResponse {
        $me = (int) $request->user()->staff_id;

        $chats = MessengerChat::forStaff($me)
            ->with(['participant1:id,sname,fname,mname', 'participant2:id,sname,fname,mname'])
            ->addSelect(['last_message_id' => MessengerMessage::select('id')
                ->whereColumn('chat_id', 'messenger_chats.id')
                ->orderByDesc('id')
                ->limit(1)])
            ->get()
            ->filter(fn (MessengerChat $chat) => $chat->last_message_id !== null);

        $lastMessages = MessengerMessage::whereIn('id', $chats->pluck('last_message_id'))->get()->keyBy('id');

        $unreadByChat = $this->unreadQuery($me)
            ->whereIn('messenger_messages.chat_id', $chats->pluck('id'))
            ->groupBy('messenger_messages.chat_id')
            ->selectRaw('messenger_messages.chat_id, COUNT(*) as unread')
            ->pluck('unread', 'chat_id');

        $items = $chats->map(function (MessengerChat $chat) use ($me, $lastMessages, $unreadByChat) {
            $peer = (int) $chat->participant_1_id === $me ? $chat->participant2 : $chat->participant1;
            $last = $lastMessages->get($chat->last_message_id);
            $peerLastRead = $chat->lastReadIdFor($chat->otherParticipantId($me));
            $isSelf = $last && (int) $last->from_id === $me;

            return [
                'chat_id' => $chat->id,
                'participant' => $peer ? [
                    'id' => $peer->id,
                    'sname' => $peer->sname,
                    'fname' => $peer->fname,
                    'mname' => $peer->mname,
                ] : null,
                'last_message' => $last ? [
                    'id' => $last->id,
                    'from_id' => $last->from_id,
                    'self' => $isSelf,
                    'text' => MessengerMessageResource::previewText($last->message, $last->media),
                    'type' => MessengerMessageResource::previewType($last->media),
                    'read' => $isSelf && $peerLastRead !== null && $peerLastRead >= $last->id,
                    'created_at' => $last->created_at->toIso8601String(),
                ] : null,
                'unread_count' => (int) ($unreadByChat[$chat->id] ?? 0),
            ];
        })->sortByDesc(fn (array $item) => $item['last_message']['id'] ?? 0)->values();

        return response()->json([
            'chats' => $items,
            'total_unread' => $items->sum('unread_count'),
        ]);
    }

    /**
     * Получить или создать чат между двумя пользователями
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrCreateChat(Request $request) {
        [
            'participant_id' => $participantId,
        ] = $request->validate([
            'participant_id' => 'required|integer|exists:staff,id',
        ]);

        $currentUserId = (int) $request->user()->staff_id;

        if ((int) $participantId === $currentUserId) {
            return response()->json(['error' => 'Нельзя создать чат с самим собой'], 422);
        }

        $chat = MessengerChat::getOrCreate($currentUserId, $participantId);

        return response()->json(['chat_id' => $chat->id]);
    }

    /**
     * Получить сообщения чата.
     * Без limit — вся история (так работают старые версии приложения).
     * limit + before_id — страница истории перед сообщением, after_id — новые сообщения после него.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMessages(Request $request) {
        $validated = $request->validate([
            'chat_id' => 'required|integer',
            'limit' => 'nullable|integer|min:1|max:100',
            'before_id' => 'nullable|integer',
            'after_id' => 'nullable|integer',
        ]);

        $chat = $this->chatForParticipant($request, $validated['chat_id']);
        if (!$chat) {
            return $this->chatNotFound();
        }

        $me = (int) $request->user()->staff_id;
        $limit = $validated['limit'] ?? null;
        $hasMore = false;

        $query = MessengerMessage::where('chat_id', $chat->id)
            ->with(['profile.registred', 'replyTo.profile']);

        if (!empty($validated['after_id'])) {
            $messages = $query->where('id', '>', $validated['after_id'])
                ->orderBy('id')
                ->limit($limit ?? 100)
                ->get();
        } elseif ($limit) {
            if (!empty($validated['before_id'])) {
                $query->where('id', '<', $validated['before_id']);
            }
            $messages = $query->orderByDesc('id')->limit($limit + 1)->get();
            $hasMore = $messages->count() > $limit;
            $messages = $messages->take($limit)->reverse()->values();
        } else {
            $messages = $query->orderBy('created_at', 'asc')->get();
        }

        return response()->json([
            'messages' => MessengerMessageResource::collection($messages),
            'has_more' => $hasMore,
            'peer_last_read_id' => $chat->lastReadIdFor($chat->otherParticipantId($me)),
        ]);
    }

    /**
     * Добавить сообщение в чат
     *
     * @param Request $request
     * @return MessengerMessageResource|\Illuminate\Http\JsonResponse
     */
    public function addMessage(Request $request) {
        $validated = $request->validate([
            'chat_id' => 'required|integer',
            'message' => 'nullable|string',
            'reply_to_id' => 'nullable|integer',
            'media' => 'nullable|array',
            'media.*' => 'file|mimes:jpeg,jpg,png,gif,bmp,webp,mp4,mov,avi,mkv,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,7z,txt,csv,mp3,wav,ogg,aac,flac,m4a|max:307200', // max 300MB на файл
        ]);

        $chat = $this->chatForParticipant($request, $validated['chat_id']);
        if (!$chat) {
            return $this->chatNotFound();
        }

        $me = (int) $request->user()->staff_id;
        $message = $validated['message'] ?? '';
        $replyToId = $validated['reply_to_id'] ?? null;

        // Проверяем, что есть хотя бы сообщение или медиа
        if (empty(trim($message)) && !$request->hasFile('media')) {
            return response()->json(['error' => 'Необходимо указать текст сообщения или прикрепить медиа'], 422);
        }

        // Ответить можно только на сообщение из этого же чата
        if ($replyToId && !MessengerMessage::where('id', $replyToId)->where('chat_id', $chat->id)->exists()) {
            return response()->json(['error' => 'Сообщение для ответа не найдено'], 422);
        }

        // Обработка медиа файлов
        $mediaArray = [];
        if ($request->hasFile('media')) {
            $files = $request->file('media');

            foreach ($files as $file) {
                $mimeType = $file->getMimeType();
                $size = $file->getSize();
                $originalFilename = $file->getClientOriginalName();

                // Сохраняем файл через Storage API в storage/app/public/messenger/messages/
                $storagePath = $file->store('messenger/messages', 'public');

                // Определяем тип медиа по MIME типу
                $type = 'file';
                if (str_starts_with($mimeType, 'image/')) {
                    $type = 'image';
                } elseif (str_starts_with($mimeType, 'video/')) {
                    $type = 'video';
                } elseif (str_starts_with($mimeType, 'audio/')) {
                    $type = 'audio';
                } elseif (in_array($mimeType, [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-powerpoint',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'text/plain',
                    'text/csv',
                ])) {
                    $type = 'document';
                } elseif (in_array($mimeType, [
                    'application/zip',
                    'application/x-rar-compressed',
                    'application/x-7z-compressed',
                ])) {
                    $type = 'archive';
                }

                // Формируем данные о медиа
                $mediaArray[] = [
                    'path' => '/storage/' . $storagePath,
                    'type' => $type,
                    'mime_type' => $mimeType,
                    'size' => $size,
                    'name' => $originalFilename,
                ];
            }
        }

        $messageRecord = MessengerMessage::create([
            'chat_id' => $chat->id,
            'from_id' => $me,
            'message' => $message,
            'reply_to_id' => $replyToId,
            'media' => !empty($mediaArray) ? $mediaArray : null,
        ]);

        $messageRecord->load(['profile.registred', 'replyTo.profile']);

        // Отправитель видел свой чат до этого сообщения включительно
        $chat->markReadBy($me, $messageRecord->id);

        $resource = new MessengerMessageResource($messageRecord);
        $peerId = $chat->otherParticipantId($me);
        $eventMessage = array_merge($resource->resolve($request), ['self' => false]);

        // После ответа клиенту: отправка не ждёт Pusher и Expo, а их сбой не ломает отправку
        app()->terminating(function () use ($chat, $peerId, $me, $eventMessage, $messageRecord) {
            $this->broadcastTo($peerId, 'message.created', ['chat_id' => $chat->id, 'message' => $eventMessage]);
            $this->sendMessagePush($peerId, $me, $messageRecord);
        });

        return $resource;
    }

    /**
     * Обновить сообщение
     *
     * @param Request $request
     * @param int $id
     * @return MessengerMessageResource|\Illuminate\Http\JsonResponse
     */
    public function updateMessage(Request $request, $id) {
        // Проверка прав
        if (!$request->user()->can('mobile-app-can-edit-comment:site')) {
            return response()->json([
                'error' => 'У вас нет прав для редактирования сообщений'
            ], 403);
        }

        [
            'message' => $message,
        ] = $request->validate([
            'message' => 'required|string',
        ]);

        $messageRecord = MessengerMessage::find($id);

        if (!$messageRecord) {
            return response()->json(['error' => 'Message not found'], 404);
        }

        // Проверяем, что пользователь - автор сообщения
        if ($messageRecord->from_id !== $request->user()->staff_id) {
            return response()->json(['error' => 'У вас нет прав для редактирования этого сообщения'], 403);
        }

        $messageRecord->message = $message;
        $messageRecord->save();

        $messageRecord->load(['profile.registred', 'replyTo.profile']);

        $resource = new MessengerMessageResource($messageRecord);
        $this->notifyPeerAboutMessage($request, $messageRecord, 'message.updated', $resource);

        return $resource;
    }

    /**
     * Удалить сообщение
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeMessage(Request $request, $id) {
        // Проверка прав
        if (!$request->user()->can('mobile-app-can-delete-comment:site')) {
            return response()->json([
                'error' => 'У вас нет прав для удаления сообщений'
            ], 403);
        }

        $messageRecord = MessengerMessage::find($id);

        if (!$messageRecord) {
            return response()->json(['success' => false], 404);
        }

        // Проверяем, что пользователь - автор сообщения
        if ($messageRecord->from_id !== $request->user()->staff_id) {
            return response()->json(['error' => 'У вас нет прав для удаления этого сообщения'], 403);
        }

        // Удаляем медиа файлы если они есть
        if ($messageRecord->media) {
            $mediaArray = [];
            if (is_array($messageRecord->media)) {
                // Проверяем, является ли это массивом медиа или одним объектом медиа
                if (isset($messageRecord->media['path'])) {
                    // Старый формат - одно медиа
                    $mediaArray = [$messageRecord->media];
                } else {
                    // Новый формат - массив медиа
                    $mediaArray = $messageRecord->media;
                }
            }

            // Удаляем все файлы
            foreach ($mediaArray as $media) {
                if (isset($media['path'])) {
                    $filePath = $media['path'];
                    // Убираем префикс /storage/ чтобы получить путь в storage/app/public/
                    $storageFilePath = str_replace('/storage/', '', $filePath);

                    if (Storage::disk('public')->exists($storageFilePath)) {
                        Storage::disk('public')->delete($storageFilePath);
                    }
                }
            }
        }

        $chat = $messageRecord->chat;
        $messageId = $messageRecord->id;
        $messageRecord->delete();

        if ($chat) {
            $peerId = $chat->otherParticipantId((int) $request->user()->staff_id);
            app()->terminating(fn () => $this->broadcastTo($peerId, 'message.deleted', [
                'chat_id' => $chat->id,
                'message_id' => $messageId,
            ]));
        }

        return response()->json(['success' => true]);
    }

    /**
     * Добавить/удалить реакцию на сообщение (toggle)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleReaction(Request $request) {
        [
            'message_id' => $messageId,
            'emoji' => $emoji,
        ] = $request->validate([
            'message_id' => 'required|integer',
            'emoji' => 'required|string|max:16',
        ]);

        $message = MessengerMessage::find($messageId);

        // Реакция — только в своём чате; чужое сообщение выглядит как несуществующее
        if (!$message || !$message->chat || !$message->chat->isParticipant((int) $request->user()->staff_id)) {
            return response()->json(['error' => 'Message not found'], 404);
        }

        $userId = $request->user()->id;
        $reactions = $message->reactions ?? [];

        // Проверяем, есть ли уже реакция от этого пользователя с таким же эмодзи
        $reactionIndex = null;
        foreach ($reactions as $index => $reaction) {
            if ($reaction['user_id'] == $userId && $reaction['emoji'] === $emoji) {
                $reactionIndex = $index;
                break;
            }
        }

        if ($reactionIndex !== null) {
            // Удаляем существующую реакцию
            array_splice($reactions, $reactionIndex, 1);
        } else {
            // Добавляем новую реакцию
            $reactions[] = [
                'user_id' => $userId,
                'emoji' => $emoji,
            ];
        }

        $message->reactions = $reactions;
        $message->save();

        $message->load(['profile.registred', 'replyTo.profile']);
        $this->notifyPeerAboutMessage($request, $message, 'message.updated', new MessengerMessageResource($message));

        return response()->json([
            'success' => true,
            'reactions' => $reactions,
        ]);
    }

    /**
     * Отметить сообщения чата прочитанными до message_id включительно.
     * Граница прочитанного только растёт; собеседник получает событие chat.read (галочки «прочитано»).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markRead(Request $request): JsonResponse {
        $validated = $request->validate([
            'chat_id' => 'required|integer',
            'message_id' => 'required|integer',
        ]);

        $chat = $this->chatForParticipant($request, $validated['chat_id']);
        if (!$chat) {
            return $this->chatNotFound();
        }

        $me = (int) $request->user()->staff_id;

        // Не дальше последнего существующего сообщения этого чата
        $lastId = MessengerMessage::where('chat_id', $chat->id)
            ->where('id', '<=', $validated['message_id'])
            ->max('id');

        if ($lastId && $chat->markReadBy($me, (int) $lastId)) {
            $peerId = $chat->otherParticipantId($me);
            app()->terminating(fn () => $this->broadcastTo($peerId, 'chat.read', [
                'chat_id' => $chat->id,
                'reader_id' => $me,
                'last_read_id' => (int) $lastId,
            ]));
        }

        return response()->json([
            'last_read_id' => $chat->lastReadIdFor($me),
            'total_unread' => $this->unreadQuery($me)->count(),
        ]);
    }

    /**
     * «Печатает…»: клиент вызывает не чаще раза в несколько секунд, собеседник получает chat.typing
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function typing(Request $request): JsonResponse {
        $validated = $request->validate([
            'chat_id' => 'required|integer',
        ]);

        $chat = $this->chatForParticipant($request, $validated['chat_id']);
        if (!$chat) {
            return $this->chatNotFound();
        }

        $me = (int) $request->user()->staff_id;
        $peerId = $chat->otherParticipantId($me);
        app()->terminating(fn () => $this->broadcastTo($peerId, 'chat.typing', [
            'chat_id' => $chat->id,
            'staff_id' => $me,
        ]));

        return response()->json(['success' => true]);
    }

    /**
     * Чат, если текущий сотрудник его участник; иначе null
     */
    private function chatForParticipant(Request $request, $chatId): ?MessengerChat {
        $chat = MessengerChat::find($chatId);

        return $chat && $chat->isParticipant((int) $request->user()->staff_id) ? $chat : null;
    }

    /**
     * Чужой чат отвечает так же, как несуществующий — не раскрываем, что он есть
     */
    private function chatNotFound(): JsonResponse {
        return response()->json(['error' => 'Чат не найден'], 404);
    }

    /**
     * Непрочитанные сообщения сотрудника: от собеседника и новее его границы прочитанного
     */
    private function unreadQuery(int $staffId) {
        return MessengerMessage::query()
            ->join('messenger_chats as c', 'c.id', '=', 'messenger_messages.chat_id')
            ->where(function ($q) use ($staffId) {
                $q->where('c.participant_1_id', $staffId)->orWhere('c.participant_2_id', $staffId);
            })
            ->where('messenger_messages.from_id', '!=', $staffId)
            ->whereRaw(
                'messenger_messages.id > COALESCE(CASE WHEN c.participant_1_id = ? THEN c.participant_1_last_read_id ELSE c.participant_2_last_read_id END, 0)',
                [$staffId]
            );
    }

    private function notifyPeerAboutMessage(Request $request, MessengerMessage $message, string $event, MessengerMessageResource $resource): void {
        $chat = $message->chat;
        if (!$chat) {
            return;
        }

        $peerId = $chat->otherParticipantId((int) $request->user()->staff_id);
        // «Своё» — с точки зрения получателя: реакцию ставят и на его собственные сообщения
        $payload = [
            'chat_id' => $chat->id,
            'message' => array_merge($resource->resolve($request), ['self' => (int) $message->from_id === $peerId]),
        ];
        app()->terminating(fn () => $this->broadcastTo($peerId, $event, $payload));
    }

    private function broadcastTo(int $staffId, string $event, array $payload): void {
        // Слишком длинное сообщение не влезет в событие Pusher — клиент дозагрузит его по message_id
        if (isset($payload['message']) && strlen(json_encode($payload, JSON_UNESCAPED_UNICODE)) > self::MAX_EVENT_BYTES) {
            $payload = [
                'chat_id' => $payload['chat_id'],
                'message_id' => $payload['message']['id'],
                'truncated' => true,
            ];
        }

        try {
            event(new MessengerEvent($staffId, $event, $payload));
        } catch (\Throwable $error) {
            // Сообщение уже сохранено: собеседник получит его при следующей загрузке чата
            Log::warning('[Messenger] Broadcast failed', ['event' => $event, 'error' => $error->getMessage()]);
        }
    }

    private function sendMessagePush(int $recipientId, int $senderId, MessengerMessage $message): void {
        try {
            $recipient = Staff::find($recipientId);
            $sender = Staff::find($senderId);
            if (!$recipient || !$sender) {
                return;
            }

            $this->pushService->sendNewMessageNotification(
                $recipient,
                $sender,
                $message,
                $this->unreadQuery($recipientId)->count()
            );
        } catch (\Throwable $error) {
            Log::warning('[Messenger] Message push failed', ['error' => $error->getMessage()]);
        }
    }
}
