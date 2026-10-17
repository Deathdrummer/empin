<?php

namespace App\Services\Messenger;

use App\Http\Resources\MessengerMessageResource;
use App\Models\MessengerChat;
use App\Models\MessengerChatMember;
use App\Models\MessengerMessage;
use App\Models\Staff;
use App\Models\TimesheetChat;
use Illuminate\Support\Facades\DB;

/**
 * Список чатов сотрудника, непрочитанные и сведения о чате.
 */
class ChatDirectory
{
    public function __construct(private ObjectChats $objects)
    {
    }

    /**
     * Непрочитанные сообщения личных чатов и групп: chat_id => count.
     * Системные сообщения («добавил в группу») непрочитанными не считаются.
     */
    public function messageUnreadByChat(int $staffId, bool $onlyAudible = false): array
    {
        return MessengerMessage::query()
            ->join('messenger_chat_members as m', function ($join) use ($staffId) {
                $join->on('m.chat_id', '=', 'messenger_messages.chat_id')->where('m.staff_id', $staffId);
            })
            ->when($onlyAudible, fn ($q) => $q->where('m.muted', false))
            ->where('messenger_messages.from_id', '!=', $staffId)
            ->where('messenger_messages.type', '!=', MessengerMessage::TYPE_SYSTEM)
            ->whereRaw('messenger_messages.id > COALESCE(m.last_read_id, 0)')
            ->groupBy('messenger_messages.chat_id')
            ->select('messenger_messages.chat_id', DB::raw('COUNT(*) as unread'))
            ->pluck('unread', 'chat_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /**
     * Значок на вкладке «Чаты» и на иконке приложения. Чаты без звука не считаются — как в Telegram.
     */
    public function totalUnread(int $staffId): int
    {
        return array_sum($this->messageUnreadByChat($staffId, true))
            + array_sum($this->objects->unreadByChat($staffId, true));
    }

    /**
     * Список чатов: закреплённые сверху (последний закреплённый первым), остальные — по последней активности.
     * Личный чат и объект без сообщений не показываются, пока их не закрепили; группа видна сразу.
     */
    public function listFor(int $staffId): array
    {
        $this->objects->syncFollowedObjects($staffId);

        $memberships = MessengerChatMember::where('staff_id', $staffId)->get()->keyBy('chat_id');
        $chats = MessengerChat::whereIn('id', $memberships->keys())->with('contract')->get();

        $objectChats = $chats->filter(fn (MessengerChat $chat) => $chat->isObject());
        $messageChats = $chats->reject(fn (MessengerChat $chat) => $chat->isObject());
        $directChats = $messageChats->filter(fn (MessengerChat $chat) => $chat->isDirect());
        $groupChats = $messageChats->filter(fn (MessengerChat $chat) => $chat->isGroup());

        $lastIds = MessengerMessage::whereIn('chat_id', $messageChats->pluck('id'))
            ->groupBy('chat_id')
            ->select('chat_id', DB::raw('MAX(id) as last_id'))
            ->pluck('last_id', 'chat_id');
        $lastMessages = MessengerMessage::whereIn('id', $lastIds->values())->with('profile')->get()->keyBy('id');

        $lastCommentIds = $this->objects->lastCommentIds($objectChats->pluck('contract_id')->all());
        $lastComments = TimesheetChat::whereIn('id', array_values($lastCommentIds))->with('profile')->get()->keyBy('id');

        $unread = $this->messageUnreadByChat($staffId) + $this->objects->unreadByChat($staffId);
        $mentioned = $this->unreadMentions($staffId);

        $peerIds = $directChats->mapWithKeys(fn (MessengerChat $chat) => [$chat->id => $chat->otherParticipantId($staffId)]);
        $peers = Staff::whereIn('id', $peerIds->values())->get(['id', 'sname', 'fname', 'mname', 'last_seen_at'])->keyBy('id');

        // Галочки «прочитано» у моего последнего сообщения: собеседник или кто-то из группы дочитал до него
        $othersRead = MessengerChatMember::whereIn('chat_id', $messageChats->pluck('id'))
            ->where('staff_id', '!=', $staffId)
            ->groupBy('chat_id')
            ->select('chat_id', DB::raw('MAX(last_read_id) as last_read'))
            ->pluck('last_read', 'chat_id');

        $membersCount = MessengerChatMember::whereIn('chat_id', $groupChats->pluck('id'))
            ->groupBy('chat_id')
            ->select('chat_id', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'chat_id');

        $items = [];
        foreach ($chats as $chat) {
            $member = $memberships[$chat->id];
            $peer = $chat->isDirect() ? $peers->get($peerIds[$chat->id] ?? 0) : null;

            if ($chat->isObject()) {
                $lastId = $lastCommentIds[$chat->contract_id] ?? null;
                $last = $lastId ? $lastComments->get($lastId) : null;
            } else {
                $lastId = $lastIds[$chat->id] ?? null;
                $last = $lastId ? $lastMessages->get($lastId) : null;
            }

            if (!$last && !$chat->isGroup() && !$member->pinned_at) {
                continue;
            }

            $isSelf = $last && (int) $last->from_id === $staffId;
            $activity = $last?->created_at ?? $chat->created_at;

            $items[] = [
                'chat_id'       => $chat->id,
                'type'          => $chat->type ?? MessengerChat::TYPE_DIRECT,
                'title'         => $this->titleOf($chat, $peer),
                'participant'   => $chat->isDirect() && $peer ? [
                    'id'    => $peer->id,
                    'sname' => $peer->sname,
                    'fname' => $peer->fname,
                    'mname' => $peer->mname,
                ] + Presence::of($peer) : null,
                'members_count' => $chat->isGroup() ? (int) ($membersCount[$chat->id] ?? 0) : null,
                'object'        => $chat->isObject() ? $this->objectInfo($chat) : null,
                'last_message'  => $last ? [
                    'id'         => $last->id,
                    'from_id'    => (int) $last->from_id,
                    'from_name'  => $isSelf ? null : self::shortName($last->profile),
                    'self'       => $isSelf,
                    'text'       => $this->previewOf($last, $chat->isObject()),
                    'type'       => $chat->isObject() ? MessengerMessageResource::previewType($last->media) : $this->typeOf($last),
                    'read'       => $isSelf && !$chat->isObject() && (int) ($othersRead[$chat->id] ?? 0) >= $last->id,
                    'created_at' => $last->created_at->toIso8601String(),
                ] : null,
                'unread_count'  => $unread[$chat->id] ?? 0,
                'mentioned'     => in_array($chat->id, $mentioned, true),
                'pinned'        => $member->pinned_at !== null,
                'muted'         => (bool) $member->muted,
                'activity_at'   => $activity?->toIso8601String(),
                '_pinned_ts'    => $member->pinned_at?->getTimestamp() ?? 0,
                '_activity_ts'  => $activity?->getTimestamp() ?? 0,
            ];
        }

        usort($items, function (array $a, array $b) {
            if (($a['_pinned_ts'] > 0) !== ($b['_pinned_ts'] > 0)) {
                return $a['_pinned_ts'] > 0 ? -1 : 1;
            }
            if ($a['_pinned_ts'] !== $b['_pinned_ts']) {
                return $b['_pinned_ts'] <=> $a['_pinned_ts'];
            }

            return [$b['_activity_ts'], $b['chat_id']] <=> [$a['_activity_ts'], $a['chat_id']];
        });

        $totalUnread = 0;
        foreach ($items as &$item) {
            if (!$item['muted']) {
                $totalUnread += $item['unread_count'];
            }
            unset($item['_pinned_ts'], $item['_activity_ts']);
        }
        unset($item);

        return ['chats' => $items, 'total_unread' => $totalUnread];
    }

    /**
     * Сведения о чате для заголовка и экрана «Информация»
     */
    public function infoFor(MessengerChat $chat, int $staffId): array
    {
        $member = $chat->member($staffId);

        $info = [
            'chat_id'    => $chat->id,
            'type'       => $chat->type ?? MessengerChat::TYPE_DIRECT,
            'title'      => null,
            'created_by' => $chat->created_by,
            'pinned'     => $member?->pinned_at !== null,
            'muted'      => (bool) $member?->muted,
            'role'       => $member?->role,
            'pinned_messages' => $this->pinnedMessages($chat),
        ];

        if ($chat->isDirect()) {
            $peer = Staff::find($chat->otherParticipantId($staffId), ['id', 'sname', 'fname', 'mname', 'last_seen_at']);
            $info['title'] = $this->titleOf($chat, $peer);
            $info['participant'] = $peer ? [
                'id'    => $peer->id,
                'sname' => $peer->sname,
                'fname' => $peer->fname,
                'mname' => $peer->mname,
            ] + Presence::of($peer) : null;

            return $info;
        }

        if ($chat->isObject()) {
            $info['title'] = $this->titleOf($chat, null);
            $info['object'] = $this->objectInfo($chat);
            $info['members_count'] = MessengerChatMember::where('chat_id', $chat->id)->count();

            return $info;
        }

        $members = MessengerChatMember::where('chat_id', $chat->id)
            ->join('staff as s', 's.id', '=', 'messenger_chat_members.staff_id')
            ->orderByRaw("messenger_chat_members.role = 'admin' DESC")
            ->orderBy('s.sname')
            ->get(['s.id', 's.sname', 's.fname', 's.mname', 's.last_seen_at', 'messenger_chat_members.role', 'messenger_chat_members.last_read_id']);

        $info['title'] = $chat->title;
        $info['members'] = $members->map(fn ($row) => [
            'id'    => (int) $row->id,
            'sname' => $row->sname,
            'fname' => $row->fname,
            'mname' => $row->mname,
            'role'  => $row->role,
        ] + Presence::of(new Staff(['last_seen_at' => $row->last_seen_at])))->values()->all();
        $info['members_count'] = count($info['members']);
        $info['online_count'] = collect($info['members'])->where('online', true)->count();

        return $info;
    }

    /**
     * Закреплённые сообщения чата, последнее закреплённое первым
     */
    public function pinnedMessages(MessengerChat $chat): array
    {
        $query = $chat->isObject()
            ? $this->objects->commentsQuery((int) $chat->contract_id)
            : MessengerMessage::where('chat_id', $chat->id);

        return $query->whereNotNull('pinned_at')
            ->orderByDesc('pinned_at')
            ->limit(10)
            ->with('profile')
            ->get()
            ->map(fn ($message) => [
                'id'        => $message->id,
                'text'      => $this->previewOf($message, $chat->isObject()),
                'from_name' => self::shortName($message->profile),
                'type'      => $chat->isObject() ? MessengerMessageResource::previewType($message->media) : $this->typeOf($message),
            ])
            ->values()
            ->all();
    }

    public function titleOf(MessengerChat $chat, ?Staff $peer): string
    {
        if ($chat->isObject()) {
            $object = $chat->relationLoaded('contract') ? $chat->contract : $chat->contract()->first();

            return trim(($object->object_number ?? '') . ' ' . ($object->title ?? '')) ?: 'Объект';
        }

        if ($chat->isGroup()) {
            return (string) $chat->title;
        }

        return $peer ? trim("{$peer->sname} {$peer->fname}") : 'Собеседник';
    }

    public function previewOf($message, bool $isComment): string
    {
        if (!$isComment && $message->type === MessengerMessage::TYPE_POLL) {
            return 'Опрос: ' . ($message->poll['question'] ?? '');
        }

        return MessengerMessageResource::previewText($message->message, $message->media);
    }

    /** «Самсонов А.» — подпись автора в группе, превью и push */
    public static function shortName(?Staff $staff): ?string
    {
        if (!$staff) {
            return null;
        }

        $initial = mb_substr((string) $staff->fname, 0, 1);

        return trim($staff->sname . ($initial !== '' ? " {$initial}." : ''));
    }

    private function typeOf(MessengerMessage $message): string
    {
        if (in_array($message->type, [MessengerMessage::TYPE_POLL, MessengerMessage::TYPE_SYSTEM], true)) {
            return $message->type;
        }

        return MessengerMessageResource::previewType($message->media);
    }

    private function objectInfo(MessengerChat $chat): array
    {
        $object = $chat->relationLoaded('contract') ? $chat->contract : $chat->contract()->first();

        return [
            'contract_id'   => (int) $chat->contract_id,
            'object_number' => $object?->object_number,
            'title'         => $object?->title,
        ];
    }

    /**
     * Чаты, где сотрудника упомянули в ещё не прочитанном сообщении
     *
     * @return int[]
     */
    private function unreadMentions(int $staffId): array
    {
        return MessengerMessage::query()
            ->join('messenger_chat_members as m', function ($join) use ($staffId) {
                $join->on('m.chat_id', '=', 'messenger_messages.chat_id')->where('m.staff_id', $staffId);
            })
            ->whereJsonContains('messenger_messages.mentions', $staffId)
            ->whereRaw('messenger_messages.id > COALESCE(m.last_read_id, 0)')
            ->distinct()
            ->pluck('messenger_messages.chat_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
