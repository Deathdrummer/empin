<?php namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessengerMessageResource;
use App\Models\MessengerChat;
use App\Models\MessengerChatMember;
use App\Models\MessengerMessage;
use App\Models\Staff;
use App\Services\Messenger\ChatDirectory;
use App\Services\Messenger\MessengerNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Групповые чаты (бригады): создание, название, состав.
 * Добавлять участников может любой участник, переименовывать и исключать — администратор (создатель).
 * События состава видны в ленте системными сообщениями.
 */
class MessengerGroupController extends Controller {

    private const MAX_MEMBERS = 200;

    public function __construct(
        private ChatDirectory $directory,
        private MessengerNotifier $notifier,
    ) {
    }

    public function create(Request $request): JsonResponse {
        $validated = $request->validate([
            'title'        => 'required|string|max:64',
            'member_ids'   => 'required|array|min:1|max:' . self::MAX_MEMBERS,
            'member_ids.*' => 'integer|exists:staff,id',
        ]);

        $title = trim($validated['title']);
        if ($title === '') {
            return response()->json(['error' => 'Укажите название группы'], 422);
        }

        $me = $this->me($request);
        $memberIds = array_values(array_diff(array_unique(array_map('intval', $validated['member_ids'])), [$me]));
        if (!$memberIds) {
            return response()->json(['error' => 'Добавьте хотя бы одного участника'], 422);
        }

        $chat = DB::transaction(function () use ($title, $me, $memberIds) {
            $chat = MessengerChat::create([
                'type'       => MessengerChat::TYPE_GROUP,
                'title'      => $title,
                'created_by' => $me,
            ]);

            $chat->ensureMember($me, null, MessengerChatMember::ROLE_ADMIN);
            foreach ($memberIds as $id) {
                $chat->ensureMember($id);
            }

            return $chat;
        });

        $actor = Staff::find($me);
        $this->systemMessage($request, $chat, $actor, ChatDirectory::shortName($actor) . " создал(а) группу «{$title}»");
        $this->afterResponse(fn () => $this->notifier->addedToGroup($chat, $actor, $memberIds));

        return response()->json(['chat_id' => $chat->id], 201);
    }

    public function rename(Request $request): JsonResponse {
        $validated = $request->validate([
            'chat_id' => 'required|integer',
            'title'   => 'required|string|max:64',
        ]);

        [$chat, $member] = $this->groupFor($request, $validated['chat_id']);
        if (!$chat) {
            return $this->notFound();
        }
        if ($member->role !== MessengerChatMember::ROLE_ADMIN) {
            return response()->json(['error' => 'Переименовать группу может только администратор'], 403);
        }

        $title = trim($validated['title']);
        if ($title === '' || $title === $chat->title) {
            return response()->json(['chat_id' => $chat->id, 'title' => $chat->title]);
        }

        $chat->update(['title' => $title]);
        $actor = Staff::find($this->me($request));
        $this->systemMessage($request, $chat, $actor, ChatDirectory::shortName($actor) . " переименовал(а) группу в «{$title}»");

        return response()->json(['chat_id' => $chat->id, 'title' => $title]);
    }

    public function addMembers(Request $request): JsonResponse {
        $validated = $request->validate([
            'chat_id'      => 'required|integer',
            'member_ids'   => 'required|array|min:1|max:' . self::MAX_MEMBERS,
            'member_ids.*' => 'integer|exists:staff,id',
        ]);

        [$chat] = $this->groupFor($request, $validated['chat_id']);
        if (!$chat) {
            return $this->notFound();
        }

        $existing = $chat->memberIds();
        $newIds = array_values(array_diff(array_unique(array_map('intval', $validated['member_ids'])), $existing));
        if (count($existing) + count($newIds) > self::MAX_MEMBERS) {
            return response()->json(['error' => 'В группе может быть не больше ' . self::MAX_MEMBERS . ' участников'], 422);
        }

        if ($newIds) {
            // Новый участник видит историю, но старые сообщения у него не непрочитанные
            $lastId = MessengerMessage::where('chat_id', $chat->id)->max('id');
            foreach ($newIds as $id) {
                $chat->ensureMember($id, $lastId !== null ? (int) $lastId : null);
            }

            $actor = Staff::find($this->me($request));
            $names = Staff::whereIn('id', $newIds)->get()->map(fn (Staff $s) => ChatDirectory::shortName($s))->implode(', ');
            $this->systemMessage($request, $chat, $actor, ChatDirectory::shortName($actor) . " добавил(а) {$names}");
            $this->afterResponse(fn () => $this->notifier->addedToGroup($chat, $actor, $newIds));
        }

        return response()->json(['chat_id' => $chat->id, 'added' => $newIds]);
    }

    public function removeMember(Request $request): JsonResponse {
        $validated = $request->validate([
            'chat_id'  => 'required|integer',
            'staff_id' => 'required|integer',
        ]);

        [$chat, $member] = $this->groupFor($request, $validated['chat_id']);
        if (!$chat) {
            return $this->notFound();
        }

        $me = $this->me($request);
        $staffId = (int) $validated['staff_id'];
        if ($staffId === $me) {
            return $this->leave($request);
        }
        if ($member->role !== MessengerChatMember::ROLE_ADMIN) {
            return response()->json(['error' => 'Исключать участников может только администратор'], 403);
        }

        $removed = MessengerChatMember::where('chat_id', $chat->id)->where('staff_id', $staffId)->delete();
        if ($removed) {
            $actor = Staff::find($me);
            $this->systemMessage($request, $chat, $actor, ChatDirectory::shortName($actor) . ' исключил(а) ' . ChatDirectory::shortName(Staff::find($staffId)));
            // Исключённый уберёт группу из списка
            $this->afterResponse(fn () => $this->notifier->broadcast([$staffId], 'chat.updated', ['chat_id' => $chat->id, 'removed' => true]));
        }

        return response()->json(['chat_id' => $chat->id, 'removed' => (bool) $removed]);
    }

    public function leave(Request $request): JsonResponse {
        $validated = $request->validate(['chat_id' => 'required|integer']);

        [$chat, $member] = $this->groupFor($request, $validated['chat_id']);
        if (!$chat) {
            return $this->notFound();
        }

        $me = $this->me($request);
        $actor = Staff::find($me);
        $member->delete();

        $remaining = MessengerChatMember::where('chat_id', $chat->id)->orderBy('id')->get();
        if ($remaining->isEmpty()) {
            $chat->delete();

            return response()->json(['chat_id' => $chat->id, 'left' => true]);
        }

        // Ушёл администратор — администратором становится тот, кто в группе дольше всех
        if ($member->role === MessengerChatMember::ROLE_ADMIN && !$remaining->contains('role', MessengerChatMember::ROLE_ADMIN)) {
            $remaining->first()->update(['role' => MessengerChatMember::ROLE_ADMIN]);
        }

        $this->systemMessage($request, $chat, $actor, ChatDirectory::shortName($actor) . ' покинул(а) группу');
        $this->afterResponse(fn () => $this->notifier->broadcast([$me], 'chat.updated', ['chat_id' => $chat->id, 'removed' => true]));

        return response()->json(['chat_id' => $chat->id, 'left' => true]);
    }

    // --- Вспомогательное ---

    private function me(Request $request): int {
        return (int) $request->user()->staff_id;
    }

    /**
     * Группа, в которой состоит текущий сотрудник, и его участие
     *
     * @return array{0: ?MessengerChat, 1: ?MessengerChatMember}
     */
    private function groupFor(Request $request, $chatId): array {
        $chat = MessengerChat::where('type', MessengerChat::TYPE_GROUP)->find($chatId);
        $member = $chat?->member($this->me($request));

        return $member ? [$chat, $member] : [null, null];
    }

    private function notFound(): JsonResponse {
        return response()->json(['error' => 'Группа не найдена'], 404);
    }

    /**
     * Системное сообщение в ленте группы и событие участникам (их списки и экраны обновятся)
     */
    private function systemMessage(Request $request, MessengerChat $chat, ?Staff $actor, string $text): void {
        $message = MessengerMessage::create([
            'chat_id' => $chat->id,
            'from_id' => $actor?->id ?? $this->me($request),
            'type'    => MessengerMessage::TYPE_SYSTEM,
            'message' => $text,
        ]);
        $chat->markReadBy($message->from_id, $message->id);

        $message->load(['profile.registred', 'votes']);
        $payload = array_merge((new MessengerMessageResource($message))->resolve($request), ['self' => false]);

        $this->afterResponse(function () use ($chat, $payload, $message) {
            $this->notifier->broadcastToChat($chat, 'message.created', ['chat_id' => $chat->id, 'message' => $payload], (int) $message->from_id);
            $this->notifier->broadcastToChat($chat, 'chat.updated', ['chat_id' => $chat->id]);
        });
    }

    private function afterResponse(callable $callback): void {
        app()->terminating($callback);
    }
}
