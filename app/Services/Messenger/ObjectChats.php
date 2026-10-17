<?php

namespace App\Services\Messenger;

use App\Models\MessengerChat;
use App\Models\MessengerChatMember;
use App\Models\TimesheetChat;
use App\Models\TimesheetContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Чаты объектов: комментарии табеля к договору за все дни и все бригады одной лентой.
 *
 * Объект попадает в «Чаты» сотрудника сам, если его бригада работала на объекте в последние
 * TEAM_DAYS дней или он писал комментарии к объекту в последние COMMENT_DAYS дней.
 * Открыть и написать в чат любого объекта может любой сотрудник — как в табеле.
 */
class ObjectChats
{
    private const TEAM_DAYS = 30;
    private const COMMENT_DAYS = 60;

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
     * Создать чаты и участие для объектов, которые сотрудник отслеживает, но ещё не открывал.
     * Новый участник не получает старые комментарии непрочитанными.
     */
    public function syncFollowedObjects(int $staffId): void
    {
        $teamContracts = TimesheetContract::query()
            ->join('timesheet_teams as t', 't.id', '=', 'timesheet_contracts.team_id')
            ->where('t.staff_id', $staffId)
            ->where('t.day', '>=', now()->subDays(self::TEAM_DAYS)->toDateString())
            ->distinct()
            ->pluck('timesheet_contracts.contract_id');

        $commentContracts = TimesheetChat::query()
            ->join('timesheet_contracts as tc', 'tc.id', '=', 'timesheet_chat.timesheet_contract_id')
            ->where('timesheet_chat.from_id', $staffId)
            ->where('timesheet_chat.created_at', '>=', now()->subDays(self::COMMENT_DAYS))
            ->distinct()
            ->pluck('tc.contract_id');

        $contractIds = $teamContracts->merge($commentContracts)->map(fn ($id) => (int) $id)->unique()->values();
        if ($contractIds->isEmpty()) {
            return;
        }

        $chats = MessengerChat::where('type', MessengerChat::TYPE_OBJECT)
            ->whereIn('contract_id', $contractIds)
            ->get()
            ->keyBy('contract_id');

        foreach ($contractIds->diff($chats->keys()) as $contractId) {
            $chats[$contractId] = MessengerChat::forObject($contractId);
        }

        $following = MessengerChatMember::where('staff_id', $staffId)
            ->whereIn('chat_id', $chats->pluck('id'))
            ->pluck('chat_id')
            ->map(fn ($id) => (int) $id);

        $newChats = $chats->reject(fn (MessengerChat $chat) => $following->contains($chat->id));
        if ($newChats->isEmpty()) {
            return;
        }

        $lastIds = $this->lastCommentIds($newChats->pluck('contract_id')->all());
        $now = now();

        MessengerChatMember::insertOrIgnore($newChats->map(fn (MessengerChat $chat) => [
            'chat_id'      => $chat->id,
            'staff_id'     => $staffId,
            'role'         => MessengerChatMember::ROLE_MEMBER,
            'last_read_id' => $lastIds[$chat->contract_id] ?? null,
            'muted'        => false,
            'created_at'   => $now,
            'updated_at'   => $now,
        ])->values()->all());
    }

    /**
     * Последний комментарий каждого объекта
     *
     * @param int[] $contractIds
     * @return array<int, int> contract_id => comment id
     */
    public function lastCommentIds(array $contractIds): array
    {
        if (!$contractIds) {
            return [];
        }

        return TimesheetChat::query()
            ->join('timesheet_contracts as tc', 'tc.id', '=', 'timesheet_chat.timesheet_contract_id')
            ->whereIn('tc.contract_id', $contractIds)
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
