<?php namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\MessengerChat;
use App\Services\Messenger\MessengerNotifier;
use App\Services\Messenger\ObjectChats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Состав чата объекта. Участников подбирает табель (мастера бригад с объектом), а здесь их меняют вручную:
 * добавляет участник чата или тот, кто ставит объекты бригадам; убирает — только тот, кто ставит объекты.
 * Выйти может любой участник. Убранный не возвращается, пока объект не поставят его бригаде снова.
 */
class MessengerObjectController extends Controller {

    private const MAX_ADD = 200;

    public function __construct(
        private ObjectChats $objects,
        private MessengerNotifier $notifier,
    ) {
    }

    public function addMembers(Request $request): JsonResponse {
        $validated = $request->validate([
            'chat_id'      => 'required|integer',
            'member_ids'   => 'required|array|min:1|max:' . self::MAX_ADD,
            'member_ids.*' => 'integer|exists:staff,id',
        ]);

        $chat = $this->objectChat($validated['chat_id']);
        if (!$chat) {
            return $this->notFound();
        }

        // Назначенные в табеле мастера — участники, даже если ещё не открывали «Чаты»
        $this->objects->syncMembers($chat);

        $me = $this->me($request);
        if (!$chat->member($me) && !$this->canAssignObjects($request)) {
            return response()->json(['error' => 'Добавлять участников могут участники чата'], 403);
        }

        $existing = $chat->memberIds();
        $newIds = array_values(array_diff(array_unique(array_map('intval', $validated['member_ids'])), $existing));
        foreach ($newIds as $staffId) {
            $this->objects->addMember($chat, $staffId, $staffId !== $me);
        }

        if ($newIds) {
            // Списки чатов новых участников и экраны «Информация» остальных обновятся
            app()->terminating(fn () => $this->notifier->broadcastToChat($chat, 'chat.updated', ['chat_id' => $chat->id]));
        }

        return response()->json(['chat_id' => $chat->id, 'added' => $newIds]);
    }

    public function removeMember(Request $request): JsonResponse {
        $validated = $request->validate([
            'chat_id'  => 'required|integer',
            'staff_id' => 'required|integer',
        ]);

        $chat = $this->objectChat($validated['chat_id']);
        if (!$chat) {
            return $this->notFound();
        }

        // Назначенные в табеле мастера — участники, даже если ещё не открывали «Чаты»
        $this->objects->syncMembers($chat);

        $me = $this->me($request);
        $staffId = (int) $validated['staff_id'];
        if ($staffId !== $me && !$this->canAssignObjects($request)) {
            return response()->json(['error' => 'Убирать участников может тот, кто ставит объекты бригадам'], 403);
        }

        $removed = $this->objects->removeMember($chat, $staffId, $me);
        if ($removed) {
            app()->terminating(function () use ($chat, $staffId) {
                // Убранный уберёт чат из списка, остальные обновят «Информацию»
                $this->notifier->broadcast([$staffId], 'chat.updated', ['chat_id' => $chat->id, 'removed' => true]);
                $this->notifier->broadcastToChat($chat, 'chat.updated', ['chat_id' => $chat->id]);
            });
        }

        return response()->json(['chat_id' => $chat->id, 'removed' => $removed]);
    }

    public function leave(Request $request): JsonResponse {
        $request->validate(['chat_id' => 'required|integer']);
        $request->merge(['staff_id' => $this->me($request)]);

        return $this->removeMember($request);
    }

    // --- Вспомогательное ---

    private function me(Request $request): int {
        return (int) $request->user()->staff_id;
    }

    private function canAssignObjects(Request $request): bool {
        return $request->user()->can('mobile-app-can-create-contract:site');
    }

    private function objectChat($chatId): ?MessengerChat {
        return MessengerChat::where('type', MessengerChat::TYPE_OBJECT)->find($chatId);
    }

    private function notFound(): JsonResponse {
        return response()->json(['error' => 'Чат объекта не найден'], 404);
    }
}
