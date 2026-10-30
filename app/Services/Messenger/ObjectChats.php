<?php

namespace App\Services\Messenger;

use App\Models\MessengerChat;
use App\Models\MessengerChatMember;
use App\Models\MessengerChatRemoval;
use App\Models\MessengerHiddenMessage;
use App\Models\MessengerSetting;
use App\Models\TimesheetChat;
use App\Models\TimesheetContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Чаты объектов: комментарии табеля к договору за все дни и все бригады одной лентой.
 *
 * Участники — мастера бригад, которым объект поставлен в табеле (за любой день), и добавленные вручную.
 * Табель сверяется при загрузке списка чатов, перед рассылкой нового сообщения и в сведениях о чате,
 * поэтому подхватываются назначения, сделанные и в приложении, и на сайте.
 * Убранный из чата (или вышедший сам) возвращается, только если объект поставят его бригаде снова.
 * Открыть и написать в чат любого объекта может любой сотрудник — как в табеле.
 */
class ObjectChats
{
    /** Комментарии объекта (договора) за все дни */
    public function commentsQuery(int $contractId): Builder
    {
        return TimesheetChat::query()->whereIn(
            'timesheet_contract_id',
            TimesheetContract::where('contract_id', $contractId)->select('id')
        );
    }

    public function lastCommentId(int $contractId): ?int
    {
        $id = $this->commentsQuery($contractId)->max('id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * Добавить сотрудника в чаты объектов, которые табель поставил его бригадам.
     * Новый участник не получает старые комментарии непрочитанными.
     */
    public function syncFollowedObjects(int $staffId): void
    {
        $assigned = $this->assignments()
            ->where('t.staff_id', $staffId)
            ->groupBy('timesheet_contracts.contract_id')
            ->select('timesheet_contracts.contract_id', DB::raw('MAX(timesheet_contracts.created_at) as assigned_at'))
            ->pluck('assigned_at', 'contract_id');
        if ($assigned->isEmpty()) {
            return;
        }

        $chats = $this->chatsForObjects($assigned->keys()->map(fn ($id) => (int) $id)->all());
        $joined = MessengerChatMember::where('staff_id', $staffId)->whereIn('chat_id', $chats->pluck('id'))->pluck('chat_id')->flip();
        $removedAt = MessengerChatRemoval::where('staff_id', $staffId)->whereIn('chat_id', $chats->pluck('id'))->pluck('created_at', 'chat_id');

        $newChats = $chats->filter(fn (MessengerChat $chat) => !isset($joined[$chat->id])
            && $this->assignedSinceRemoval($assigned[$chat->contract_id] ?? null, $removedAt[$chat->id] ?? null));
        if ($newChats->isEmpty()) {
            return;
        }

        $lastIds = $this->lastCommentIds($newChats->pluck('contract_id')->all());
        $muted = MessengerSetting::muteObjects($staffId);

        $this->insertMembers($newChats->map(fn (MessengerChat $chat) => [
            'chat_id'      => $chat->id,
            'staff_id'     => $staffId,
            'last_read_id' => $lastIds[$chat->contract_id] ?? null,
            'muted'        => $muted,
        ])->values()->all());
    }

    /**
     * Добавить в чат объекта всех мастеров бригад, которым объект поставлен в табеле
     */
    public function syncMembers(MessengerChat $chat): void
    {
        if (!$chat->isObject()) {
            return;
        }

        $assigned = $this->assignments()
            ->where('timesheet_contracts.contract_id', $chat->contract_id)
            ->groupBy('t.staff_id')
            ->select('t.staff_id', DB::raw('MAX(timesheet_contracts.created_at) as assigned_at'))
            ->pluck('assigned_at', 'staff_id');

        $joined = MessengerChatMember::where('chat_id', $chat->id)->pluck('staff_id')->flip();
        $removedAt = MessengerChatRemoval::where('chat_id', $chat->id)->pluck('created_at', 'staff_id');

        $newIds = $assigned->keys()
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $staffId) => !isset($joined[$staffId])
                && $this->assignedSinceRemoval($assigned[$staffId], $removedAt[$staffId] ?? null))
            ->values();
        if ($newIds->isEmpty()) {
            return;
        }

        $lastId = $this->lastCommentId((int) $chat->contract_id);
        $muted = MessengerSetting::whereIn('staff_id', $newIds)->pluck('mute_objects', 'staff_id');

        $this->insertMembers($newIds->map(fn (int $staffId) => [
            'chat_id'      => $chat->id,
            'staff_id'     => $staffId,
            'last_read_id' => $lastId,
            'muted'        => (bool) ($muted[$staffId] ?? false),
        ])->all());
    }

    /**
     * Добавить сотрудника вручную. Добавленный другим человеком получает звук по своей настройке
     * «чаты объектов без звука», вступивший сам (написал в чат) — со звуком.
     */
    public function addMember(MessengerChat $chat, int $staffId, bool $byOther = true): MessengerChatMember
    {
        MessengerChatRemoval::where('chat_id', $chat->id)->where('staff_id', $staffId)->delete();

        return MessengerChatMember::firstOrCreate(['chat_id' => $chat->id, 'staff_id' => $staffId], [
            'role'         => MessengerChatMember::ROLE_MEMBER,
            'last_read_id' => $this->lastCommentId((int) $chat->contract_id),
            'muted'        => $byOther && MessengerSetting::muteObjects($staffId),
        ]);
    }

    /**
     * Убрать сотрудника из чата объекта (или он вышел сам). Табель не вернёт его,
     * пока объект не поставят его бригаде снова.
     */
    public function removeMember(MessengerChat $chat, int $staffId, int $removedBy): bool
    {
        $removed = MessengerChatMember::where('chat_id', $chat->id)->where('staff_id', $staffId)->delete() > 0;

        // Отметка пересоздаётся: важно время последнего удаления
        MessengerChatRemoval::where('chat_id', $chat->id)->where('staff_id', $staffId)->delete();
        MessengerChatRemoval::create(['chat_id' => $chat->id, 'staff_id' => $staffId, 'removed_by' => $removedBy]);

        return $removed;
    }

    /**
     * «Чаты объектов без звука»: сразу для всех объектов сотрудника, а новые объекты приходят без звука.
     * Выключение возвращает звук всем объектам.
     */
    public function setMuteObjects(int $staffId, bool $mute): void
    {
        MessengerSetting::updateOrCreate(['staff_id' => $staffId], ['mute_objects' => $mute]);

        MessengerChatMember::where('staff_id', $staffId)
            ->whereIn('chat_id', MessengerChat::where('type', MessengerChat::TYPE_OBJECT)->select('id'))
            ->update(['muted' => $mute]);
    }

    /** Назначения объектов бригадам: строки табеля с мастером бригады (t.staff_id) */
    private function assignments(): Builder
    {
        return TimesheetContract::query()->join('timesheet_teams as t', 't.id', '=', 'timesheet_contracts.team_id');
    }

    /**
     * Чаты объектов по договорам; недостающие создаются одним запросом
     *
     * @param int[] $contractIds
     * @return Collection<int, MessengerChat>
     */
    private function chatsForObjects(array $contractIds): Collection
    {
        $load = fn () => MessengerChat::where('type', MessengerChat::TYPE_OBJECT)->whereIn('contract_id', $contractIds)->get();
        $chats = $load();

        $missing = array_values(array_diff($contractIds, $chats->pluck('contract_id')->all()));
        if ($missing) {
            $now = now();
            // Параллельный запрос мог создать тот же чат — уникальный contract_id не даст дубля
            MessengerChat::insertOrIgnore(array_map(fn (int $contractId) => [
                'type'        => MessengerChat::TYPE_OBJECT,
                'contract_id' => $contractId,
                'created_at'  => $now,
                'updated_at'  => $now,
            ], $missing));
            $chats = $load();
        }

        return $chats;
    }

    /** Не убран из чата, или объект поставили его бригаде уже после этого */
    private function assignedSinceRemoval(?string $assignedAt, ?Carbon $removedAt): bool
    {
        return $removedAt === null || ($assignedAt !== null && Carbon::parse($assignedAt)->gt($removedAt));
    }

    private function insertMembers(array $rows): void
    {
        $now = now();
        MessengerChatMember::insertOrIgnore(array_map(fn (array $row) => $row + [
            'role'       => MessengerChatMember::ROLE_MEMBER,
            'created_at' => $now,
            'updated_at' => $now,
        ], $rows));
    }

    /**
     * Последний комментарий каждого объекта; $hiddenFor — без удалённых этим сотрудником «у себя» (список чатов)
     *
     * @param int[] $contractIds
     * @return array<int, int> contract_id => comment id
     */
    public function lastCommentIds(array $contractIds, ?int $hiddenFor = null): array
    {
        if (!$contractIds) {
            return [];
        }

        return TimesheetChat::query()
            ->join('timesheet_contracts as tc', 'tc.id', '=', 'timesheet_chat.timesheet_contract_id')
            ->whereIn('tc.contract_id', $contractIds)
            ->when($hiddenFor !== null, fn ($q) => $q
                ->join('messenger_chats as c', function ($join) {
                    $join->on('c.contract_id', '=', 'tc.contract_id')->where('c.type', MessengerChat::TYPE_OBJECT);
                })
                ->tap(fn ($q) => MessengerHiddenMessage::excludeFor($q, $hiddenFor, 'c.id', 'timesheet_chat.id')))
            ->groupBy('tc.contract_id')
            ->select('tc.contract_id', DB::raw('MAX(timesheet_chat.id) as last_id'))
            ->pluck('last_id', 'contract_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Непрочитанные комментарии в отслеживаемых сотрудником объектах
     *
     * @return array<int, int> chat_id => count
     */
    public function unreadByChat(int $staffId, bool $onlyAudible = false): array
    {
        return TimesheetChat::query()
            ->join('timesheet_contracts as tc', 'tc.id', '=', 'timesheet_chat.timesheet_contract_id')
            ->join('messenger_chats as c', function ($join) {
                $join->on('c.contract_id', '=', 'tc.contract_id')->where('c.type', MessengerChat::TYPE_OBJECT);
            })
            ->join('messenger_chat_members as m', function ($join) use ($staffId) {
                $join->on('m.chat_id', '=', 'c.id')->where('m.staff_id', $staffId);
            })
            ->when($onlyAudible, fn ($q) => $q->where('m.muted', false))
            ->where('timesheet_chat.from_id', '!=', $staffId)
            ->whereRaw('timesheet_chat.id > COALESCE(m.last_read_id, 0)')
            ->tap(fn ($q) => MessengerHiddenMessage::excludeFor($q, $staffId, 'c.id', 'timesheet_chat.id'))
            ->groupBy('c.id')
            ->select('c.id', DB::raw('COUNT(*) as unread'))
            ->pluck('unread', 'id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /**
     * Куда записать комментарий из мессенджера: объект в табеле на сегодня или ближайший прошедший день,
     * а если объект только запланирован — ближайший будущий день
     */
    public function targetTimesheetContract(int $contractId): ?TimesheetContract
    {
        $today = today()->toDateString();
        $base = fn () => TimesheetContract::query()
            ->select('timesheet_contracts.*')
            ->join('timesheet_teams as t', 't.id', '=', 'timesheet_contracts.team_id')
            ->where('timesheet_contracts.contract_id', $contractId);

        return $base()->where('t.day', '<=', $today)->orderByDesc('t.day')->orderByDesc('timesheet_contracts.id')->first()
            ?? $base()->where('t.day', '>', $today)->orderBy('t.day')->orderBy('timesheet_contracts.id')->first();
    }

    /**
     * Чат объекта комментария (для событий после действий в табеле)
     */
    public function chatForComment(TimesheetChat $comment): ?MessengerChat
    {
        $contractId = TimesheetContract::whereKey($comment->timesheet_contract_id)->value('contract_id');

        return $contractId ? MessengerChat::forObject((int) $contractId) : null;
    }
}
