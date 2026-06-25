<?php namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessengerMessageResource;
use App\Models\MessengerChat;
use App\Models\MessengerMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessengerApiController extends Controller {

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
            'participant_id' => 'required|integer',
        ]);

        $currentUserId = $request->user()->staff_id;

        $chat = MessengerChat::getOrCreate($currentUserId, $participantId);

        return response()->json(['chat_id' => $chat->id]);
    }

    /**
     * Получить сообщения чата
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getMessages(Request $request) {
        [
            'chat_id' => $chatId,
        ] = $request->validate([
            'chat_id' => 'required|integer',
        ]);

        $messages = MessengerMessage::where('chat_id', $chatId)
            ->with(['profile.registred', 'replyTo'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'messages' => MessengerMessageResource::collection($messages)
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

        $chatId = $validated['chat_id'];
        $message = $validated['message'] ?? '';
        $replyToId = $validated['reply_to_id'] ?? null;

        // Проверяем, что есть хотя бы сообщение или медиа
        if (empty(trim($message)) && !$request->hasFile('media')) {
            return response()->json(['error' => 'Необходимо указать текст сообщения или прикрепить медиа'], 422);
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
            'chat_id' => $chatId,
            'from_id' => $request->user()->staff_id,
            'message' => $message,
            'reply_to_id' => $replyToId,
            'media' => !empty($mediaArray) ? $mediaArray : null,
        ]);

        $messageRecord->load('profile.registred');

        return new MessengerMessageResource($messageRecord);
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

        $messageRecord->load('profile.registred');

        return new MessengerMessageResource($messageRecord);
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

        $messageRecord->delete();

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
            'emoji' => 'required|string',
        ]);

        $message = MessengerMessage::find($messageId);

        if (!$message) {
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

        return response()->json([
            'success' => true,
            'reactions' => $reactions,
        ]);
    }
}
