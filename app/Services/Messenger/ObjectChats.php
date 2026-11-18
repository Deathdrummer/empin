<?php

namespace App\Services\Messenger;

use App\Models\MessengerChat;
use App\Models\MessengerChatMember;
use App\Models\MessengerChatRemoval;
use App\Models\MessengerHiddenMessage;
use App\Models\MessengerSetting;
use App\Models\TimesheetChat;
use App\Models\TimesheetContract;
use App\Models\Erp\BrigadeMember;
use App\Production\Support\OrgClock;
use App\Production\Tasks\TaskAccess;
use App\Production\Tasks\TaskFacts;
use App\Production\Tasks\TaskVisibility;
use App\Production\Tasks\Viewer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Чаты объектов: переписка заданий объекта одной лентой (ТЗ п. 1.2 — переписка задания строится на мессенджере).
 *
 * Каждый видит в ленте только переписку заданий, которые видит сам (П-ЗД-20…24; решение по находке 11, 18.11):
 * лента, список чатов, непрочитанные, поиск, медиа, закреплённые и события. Писать — в доступное задание объекта.
 * Участники — те, у кого есть личное основание видеть задание объекта (создатель, исполнитель, член бригады,
 * водитель, приглашённый), и добавленные вручную; право «Просматривать все задания» в участники не добавляет
 * (такой сотрудник открывает чат поиском). Убранный из чата возвращается, только если появится новое задание.
 */
class ObjectChats
{
    /** @var array<int, Viewer> сотрудник => права и бригады на сегодня (в пределах запроса) */
    private array $viewers = [];

    public function __construct(private readonly OrgClock $clock, private readonly TaskAccess $access)
    {
    }

    /** Комментарии объекта (договора), которые видит сотрудник: только переписка видимых ему заданий */
    public function commentsQuery(int $contractId, int $staffId): Builder
    {
        return TimesheetChat::query()->whereIn('timesheet_contract_id', $this->visibleTasks($staffId, $contractId));
    }

    /** Граница «прочитано до» — по всем комментариям объекта: это номер, а не содержимое */
    public function lastCommentId(int $contractId): ?int
    {
        $id = TimesheetChat::query()
            ->whereIn('timesheet_contract_id', TimesheetContract::where('contract_id', $contractId)->select('id'))
            ->max('id');

        return $id !== null ? (int) $id : null;
    }

    /** Подзапрос id заданий, которые сотрудник видит (по объекту — если указан) */
    public function visibleTasks(int $staffId, ?int $contractId = null): Builder
    {
        return (new TaskVisibility())->query($this->viewer($staffId))
            ->when($contractId !== null, fn ($q) => $q->where('timesheet_contracts.contract_id', $contractId))
            ->select('timesheet_contracts.id');
    }

    /** Есть ли у сотрудника видимое задание на объекте — тогда он может писать в чат объекта (П-ЗД-23) */
    public function canWrite(int $contractId, int $staffId): bool
    {
        return $this->visibleTasks($staffId, $contractId)->exists();
    }

    /**
     * Куда записать сообщение из чата объекта: ответ — в задание исходного сообщения; иначе — в видимое сотруднику
     * задание на сегодня или ближайший прошедший день, а если объект только запланирован — ближайший будущий
     */
    public function targetFor(int $contractId, int $staffId, ?int $replyToId = null): ?TimesheetContract
    {
        if ($replyToId) {
            $reply = $this->commentsQuery($contractId, $staffId)->whereKey($replyToId)->first();

            return $reply ? TimesheetContract::find($reply->timesheet_contract_id) : null;
        }
        $today = $this->clock->today()->toDateString();
        $base = fn () => TimesheetContract::query()
            ->select('timesheet_contracts.*')
            ->join('timesheet_teams as t', 't.id', '=', 'timesheet_contracts.team_id')
            ->whereIn('timesheet_contracts.id', $this->visibleTasks($staffId, $contractId));

        return $base()->where('t.day', '<=', $today)->orderByDesc('t.day')->orderByDesc('timesheet_contracts.id')->first()
            ?? $base()->where('t.day', '>', $today)->orderBy('t.day')->orderBy('timesheet_contracts.id')->first();
    }

    /**
     * Кому из сотрудников можно показать сообщение задания: события и уведомления уходят только тем, кто видит
     * задание (П-ЗД-20); после отзыва приглашения или смены исполнителя новых сообщений прежние не получают
     *
     * @param int[] $staffIds
     * @return int[]
     */
    public function whoCanSee(int $taskId, array $staffIds): array
    {
        $task = TimesheetContract::find($taskId);
        if (!$task) {
            return [];
        }
        $facts = TaskFacts::fromModel($task);

        return array_values(array_filter(
            array_map('intval', $staffIds),
            fn (int $staffId) => $this->access->canView($this->viewer($staffId), $facts)
        ));
    }

    private function viewer(int $staffId): Viewer
    {
        return $this->viewers[$staffId] ??= Viewer::load($staffId, $this->clock->today());
    }

    /**
     * Добавить сотрудника в чаты объектов, где у него есть задание по личному основанию (создатель, исполнитель,
     * член бригады, водитель, приглашённый). Новый участник не получает старые комментарии непрочитанными.
     */
    public function syncFollowedObjects(int $staffId): void
    {
        $assigned = (new TaskVisibility())->query($this->viewer($staffId), withViewAll: false)
            ->groupBy('timesheet_contracts.contract_id')
            ->select('timesheet_contracts.contract_id', DB::raw('MAX(timesheet_contracts.created_at) as assigned_at'))
            ->toBase()
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
     * Добавить в чат объекта всех, у кого есть личное основание видеть его задания: создатели, исполнители-сотрудники,
     * текущие члены бригад-исполнителей, водители, приглашённые (П-ЗД-20, без права «Просматривать все задания»)
     */
    public function syncMembers(MessengerChat $chat): void
    {
        if (!$chat->isObject()) {
            return;
        }

        $assigned = $this->participants((int) $chat->contract_id);

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

    /**
     * Сотрудники с личным основанием на задания объекта и время их последнего задания
     *
     * @return Collection<int, string> staff_id => assigned_at
     */
    private function participants(int $contractId): Collection
    {
        $tasks = TimesheetContract::query()
            ->join('timesheet_teams as t', 't.id', '=', 'timesheet_contracts.team_id')
            ->where('timesheet_contracts.contract_id', $contractId)
            ->get(['timesheet_contracts.id', 'timesheet_contracts.created_at', 'timesheet_contracts.creator_staff_id',
                'timesheet_contracts.driver_staff_id', 't.staff_id', 't.brigade_id']);
        if ($tasks->isEmpty()) {
            return collect();
        }

        $result = collect();
        $note = function ($staffId, $at) use (&$result) {
            if (!$staffId) {
                return;
            }
            $staffId = (int) $staffId;
            $at = (string) $at;
            if (!isset($result[$staffId]) || $result[$staffId] < $at) {
                $result[$staffId] = $at;
            }
        };

        $members = BrigadeMember::activeOn($this->clock->today())
            ->whereIn('brigade_id', $tasks->pluck('brigade_id')->filter()->unique())
            ->get(['brigade_id', 'staff_id'])
            ->groupBy('brigade_id');
        $invites = DB::table('timesheet_contract_invites')
            ->whereIn('timesheet_contract_id', $tasks->pluck('id'))
            ->whereNull('revoked_at')
            ->get(['timesheet_contract_id', 'staff_id'])
            ->groupBy('timesheet_contract_id');

        foreach ($tasks as $task) {
            $at = $task->created_at;
            $note($task->creator_staff_id, $at);
            $note($task->driver_staff_id, $at);
            if ($task->brigade_id) {
                foreach ($members->get($task->brigade_id, collect()) as $member) {
                    $note($member->staff_id, $at);
                }
            } else {
                $note($task->staff_id, $at);
            }
            foreach ($invites->get($task->id, collect()) as $invite) {
                $note($invite->staff_id, $at);
            }
        }

        return $result;
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
            // Комментарии до вступления мастер не читал — «Прочитали» и галочки их не засчитывают
            'joined_read_id' => $row['last_read_id'] ?? null,
            'role'       => MessengerChatMember::ROLE_MEMBER,
            'created_at' => $now,
            'updated_at' => $now,
        ], $rows));
    }

    /**
     * Последний комментарий каждого объекта; $hiddenFor — для списка чатов этого сотрудника: только переписка
     * видимых ему заданий и без удалённого им «у себя». Без него — граница прочитанного для новых участников
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
            ->when($hiddenFor !== null, fn ($q) => $q->whereIn('timesheet_chat.timesheet_contract_id', $this->visibleTasks($hiddenFor)))
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
            ->when($onlyAudible, fn ($q) => $q->where('m.muted', false)->whereNull('m.archived_at'))
            ->whereIn('timesheet_chat.timesheet_contract_id', $this->visibleTasks($staffId))
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
     * Чат объекта комментария (для событий после действий в табеле)
     */
    public function chatForComment(TimesheetChat $comment): ?MessengerChat
    {
        $contractId = TimesheetContract::whereKey($comment->timesheet_contract_id)->value('contract_id');

        return $contractId ? MessengerChat::forObject((int) $contractId) : null;
    }
}
