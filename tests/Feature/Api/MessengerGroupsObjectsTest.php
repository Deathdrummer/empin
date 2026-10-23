<?php

namespace Tests\Feature\Api;

use App\Events\MessengerEvent;
use App\Models\MessengerChat;
use App\Models\MessengerChatMember;
use App\Models\MessengerMessage;
use App\Models\Staff;
use App\Models\StaffPushToken;
use App\Models\TimesheetChat;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Мессенджер: группы (бригады), чаты объектов, опросы, упоминания, закрепление, «без звука», поиск, «в сети».
 */
class MessengerGroupsObjectsTest extends TestCase
{
    use DatabaseTransactions;

    private Staff $me;
    private Staff $peer;
    private Staff $third;
    private Staff $outsider;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([MessengerEvent::class]);
        Http::fake(['exp.host/*' => Http::response(['data' => [['status' => 'ok', 'id' => 'ticket']]])]);
        Cache::flush();

        $this->me = $this->createStaff('Сергей', 'Иванов');
        $this->peer = $this->createStaff('Алексей', 'Петров');
        $this->third = $this->createStaff('Артём', 'Самсонов');
        $this->outsider = $this->createStaff('Олег', 'Посторонний');
    }

    public function test_group_lifecycle_admin_rights_and_system_messages(): void
    {
        $this->token($this->peer, 'ExponentPushToken[peer]');
        $this->actingAsStaff($this->me);

        $chatId = $this->postJson('/api/messenger/group', [
            'title' => 'Бригада Иванова',
            'member_ids' => [$this->peer->id, $this->third->id, $this->me->id],
        ])->assertCreated()->json('chat_id');

        $chat = MessengerChat::find($chatId);
        $this->assertSame('group', $chat->type);
        $this->assertSame('admin', $chat->member($this->me->id)->role);
        $this->assertEqualsCanonicalizing([$this->me->id, $this->peer->id, $this->third->id], $chat->memberIds());
        $this->assertSame('Иванов С. создал(а) группу «Бригада Иванова»', MessengerMessage::where('chat_id', $chatId)->value('message'));
        $this->assertPushSent('ExponentPushToken[peer]', 'Бригада Иванова', 'Иванов С. добавил(а) вас в группу');

        // Список участника: группа видна сразу, системное сообщение не «непрочитанное»
        $this->actingAsStaff($this->peer);
        $item = collect($this->getJson('/api/messenger/chats')->assertOk()->json('chats'))->firstWhere('chat_id', $chatId);
        $this->assertSame('Бригада Иванова', $item['title']);
        $this->assertSame(3, $item['members_count']);
        $this->assertSame(0, $item['unread_count']);
        $this->assertSame('system', $item['last_message']['type']);

        // Не администратор не переименовывает и не исключает, но добавлять может
        $this->postJson('/api/messenger/group/rename', ['chat_id' => $chatId, 'title' => 'Новое'])->assertForbidden();
        $this->postJson('/api/messenger/group/members/remove', ['chat_id' => $chatId, 'staff_id' => $this->third->id])->assertForbidden();
        $this->postJson('/api/messenger/group/members/add', ['chat_id' => $chatId, 'member_ids' => [$this->outsider->id]])
            ->assertOk()->assertJsonPath('added', [$this->outsider->id]);

        // Посторонний до добавления не видел группу, после — видит историю без «непрочитанных»
        $this->actingAsStaff($this->outsider);
        $this->postJson('/api/messenger/chat/messages', ['chat_id' => $chatId, 'limit' => 20])->assertOk();

        $this->actingAsStaff($this->me);
        $this->postJson('/api/messenger/group/rename', ['chat_id' => $chatId, 'title' => 'Бригада №1'])->assertOk();
        $this->postJson('/api/messenger/group/members/remove', ['chat_id' => $chatId, 'staff_id' => $this->third->id])
            ->assertOk()->assertJsonPath('removed', true);

        $this->actingAsStaff($this->third);
        $this->postJson('/api/messenger/chat/messages', ['chat_id' => $chatId, 'limit' => 20])->assertNotFound();

        // Ушёл администратор — администратором становится старейший участник
        $this->actingAsStaff($this->me);
        $this->postJson('/api/messenger/group/leave', ['chat_id' => $chatId])->assertOk();
        $this->assertSame('admin', $chat->member($this->peer->id)->role);
        $this->assertSame('Бригада №1', $chat->fresh()->title);
    }

    public function test_group_message_respects_mute_and_mentions(): void
    {
        $chat = $this->group([$this->peer, $this->third]);
        $this->token($this->peer, 'ExponentPushToken[peer]');
        $this->token($this->third, 'ExponentPushToken[third]');

        $this->actingAsStaff($this->third);
        $this->postJson('/api/messenger/chat/mute', ['chat_id' => $chat->id, 'muted' => true])->assertOk()->assertJsonPath('muted', true);

        $this->actingAsStaff($this->me);
        $this->postJson('/api/messenger/message', ['chat_id' => $chat->id, 'message' => 'Завтра в 8 на ТП-9'])->assertCreated();

        Event::assertDispatched(MessengerEvent::class, fn (MessengerEvent $event) => $event->eventName === 'message.created'
            && $event->recipientStaffIds === [$this->peer->id, $this->third->id]);
        $this->assertPushSent('ExponentPushToken[peer]', 'Бригада', 'Иванов С.: Завтра в 8 на ТП-9');
        Http::assertNotSent(fn (HttpRequest $request) => ($request[0]['to'] ?? null) === 'ExponentPushToken[third]');

        // Упоминание приходит даже в чат «без звука»; упомянуть постороннего нельзя
        $id = $this->postJson('/api/messenger/message', [
            'chat_id' => $chat->id,
            'message' => '@Самсонов А. возьми ключи',
            'mention_ids' => [$this->third->id, $this->outsider->id],
        ])->assertCreated()->assertJsonPath('mentions', [$this->third->id])->json('id');
        $this->assertPushSent('ExponentPushToken[third]', 'Бригада', 'Иванов С. упомянул(а) вас: @Самсонов А. возьми ключи');

        // В значке непрочитанных чат «без звука» не учитывается, но упоминание видно
        $this->actingAsStaff($this->third);
        $list = $this->getJson('/api/messenger/chats')->assertOk();
        $item = collect($list->json('chats'))->firstWhere('chat_id', $chat->id);
        $this->assertSame(2, $item['unread_count']);
        $this->assertTrue($item['muted']);
        $this->assertTrue($item['mentioned']);
        $this->assertSame(0, $list->json('total_unread'));

        $this->postJson('/api/messenger/chat/read', ['chat_id' => $chat->id, 'message_id' => $id])->assertOk();
        $item = collect($this->getJson('/api/messenger/chats')->json('chats'))->firstWhere('chat_id', $chat->id);
        $this->assertSame(0, $item['unread_count']);
        $this->assertFalse($item['mentioned']);
    }

    public function test_poll_only_in_groups_and_single_choice_vote_replaces(): void
    {
        $direct = MessengerChat::getOrCreate($this->me->id, $this->peer->id);
        $group = $this->group([$this->peer, $this->third]);
        $this->actingAsStaff($this->me);

        $poll = ['question' => 'Выход 17.10, ТП-9', 'options' => ['Буду', 'Не смогу']];
        $this->postJson('/api/messenger/message', ['chat_id' => $direct->id, 'type' => 'poll', 'poll' => $poll])->assertStatus(422);

        $id = $this->postJson('/api/messenger/message', ['chat_id' => $group->id, 'type' => 'poll', 'poll' => $poll])
            ->assertCreated()
            ->assertJsonPath('type', 'poll')
            ->assertJsonPath('poll.options.0.text', 'Буду')
            ->assertJsonPath('poll.options.1.voters', [])
            ->json('id');

        $this->actingAsStaff($this->peer);
        $this->postJson('/api/messenger/poll/vote', ['message_id' => $id, 'options' => [1]])->assertOk();
        $this->postJson('/api/messenger/poll/vote', ['message_id' => $id, 'options' => [0, 1]])
            ->assertOk()
            ->assertJsonPath('message.poll.options.0.voters', [$this->peer->id])
            ->assertJsonPath('message.poll.options.1.voters', [])
            ->assertJsonPath('message.poll.total_voters', 1);

        Event::assertDispatched(MessengerEvent::class, fn (MessengerEvent $event) => $event->eventName === 'message.updated'
            && $event->payload['message']['id'] === $id);

        $this->actingAsStaff($this->outsider);
        $this->postJson('/api/messenger/poll/vote', ['message_id' => $id, 'options' => [0]])->assertNotFound();
    }

    public function test_pinned_chats_go_first_and_pinned_messages_are_shared(): void
    {
        $older = MessengerChat::getOrCreate($this->me->id, $this->peer->id);
        $newer = MessengerChat::getOrCreate($this->me->id, $this->third->id);
        $pinMe = $this->message($older, $this->peer, 'Ключи от РУ у охраны, код 4512');
        $this->message($newer, $this->third, 'Позже');
        $this->actingAsStaff($this->me);

        $this->assertSame([$newer->id, $older->id], collect($this->getJson('/api/messenger/chats')->json('chats'))->pluck('chat_id')->all());

        $this->postJson('/api/messenger/chat/pin', ['chat_id' => $older->id, 'pinned' => true])->assertOk()->assertJsonPath('pinned', true);
        $chats = $this->getJson('/api/messenger/chats')->json('chats');
        $this->assertSame([$older->id, $newer->id], collect($chats)->pluck('chat_id')->all());
        $this->assertTrue($chats[0]['pinned']);

        $this->postJson('/api/messenger/message/pin', ['chat_id' => $older->id, 'message_id' => $pinMe->id, 'pinned' => true])
            ->assertOk()
            ->assertJsonPath('pinned_messages.0.id', $pinMe->id)
            ->assertJsonPath('pinned_messages.0.text', 'Ключи от РУ у охраны, код 4512');
        Event::assertDispatched(MessengerEvent::class, fn (MessengerEvent $event) => $event->eventName === 'chat.pinned'
            && in_array($this->peer->id, $event->recipientStaffIds, true));

        // Закрепление не делает сообщение «изменённым»
        $this->assertEquals($pinMe->updated_at, $pinMe->fresh()->updated_at);

        $this->actingAsStaff($this->peer);
        $this->getJson('/api/messenger/chat/info?chat_id=' . $older->id)->assertOk()->assertJsonPath('pinned_messages.0.id', $pinMe->id);
        $this->postJson('/api/messenger/message/pin', ['chat_id' => $older->id, 'message_id' => $pinMe->id, 'pinned' => false])
            ->assertOk()->assertJsonPath('pinned_messages', []);
    }

    public function test_search_finds_only_own_chats_objects_and_object_comments(): void
    {
        $mine = MessengerChat::getOrCreate($this->me->id, $this->peer->id);
        $foreign = MessengerChat::getOrCreate($this->peer->id, $this->outsider->id);
        $hit = $this->message($mine, $this->peer, 'Фото щита после монтажа');
        $this->message($foreign, $this->outsider, 'Щит чужой');

        $contractId = $this->contract('112', 'ТП-14 Северная');
        $timesheetContractId = $this->assignToTeam($contractId, $this->me, today()->toDateString());
        $comment = $this->comment($timesheetContractId, $this->peer, 'Щит смонтирован');

        $this->actingAsStaff($this->me);
        $this->getJson('/api/messenger/chats')->assertOk(); // объект попадает в отслеживаемые

        $result = $this->getJson('/api/messenger/search?q=' . urlencode('щит'))->assertOk();
        $ids = collect($result->json('messages'))->map(fn ($m) => $m['chat_type'] . ':' . $m['message_id'])->all();
        $this->assertEqualsCanonicalizing(['direct:' . $hit->id, 'object:' . $comment->id], $ids);

        $objects = $this->getJson('/api/messenger/search?q=112')->assertOk()->json('chats');
        $this->assertSame('object', $objects[0]['type']);
        $this->assertSame($contractId, $objects[0]['contract_id']);

        // Поиск внутри чужого чата недоступен
        $this->getJson('/api/messenger/search?q=' . urlencode('щит') . '&chat_id=' . $foreign->id)->assertNotFound();
    }

    public function test_presence_online_and_last_seen(): void
    {
        $chat = MessengerChat::getOrCreate($this->me->id, $this->peer->id);

        $this->actingAsStaff($this->peer);
        $this->getJson('/api/messenger/chats')->assertOk();
        $this->assertNotNull($this->peer->fresh()->last_seen_at);

        $this->actingAsStaff($this->me);
        $this->getJson('/api/messenger/chat/info?chat_id=' . $chat->id)
            ->assertOk()
            ->assertJsonPath('participant.online', true);

        Staff::whereKey($this->peer->id)->update(['last_seen_at' => now()->subMinutes(10)]);
        $info = $this->getJson('/api/messenger/chat/info?chat_id=' . $chat->id)->assertOk();
        $this->assertFalse($info->json('participant.online'));
        $this->assertNotNull($info->json('participant.last_seen_at'));
    }

    public function test_object_chat_follow_unread_and_posting_into_timesheet(): void
    {
        $this->givePermissions(['mobile-app-can-create-comment:site']);
        $contractId = $this->contract('112', 'ТП-14 Северная');
        $yesterday = $this->assignToTeam($contractId, $this->peer, today()->subDay()->toDateString());
        $today = $this->assignToTeam($contractId, $this->me, today()->toDateString());
        $old = $this->comment($yesterday, $this->peer, 'Кабель привезли');
        $this->token($this->me, 'ExponentPushToken[me]');

        // Мастер бригады с объектом видит чат объекта; комментарии до этого не «непрочитанные»
        $this->actingAsStaff($this->me);
        $item = collect($this->getJson('/api/messenger/chats')->assertOk()->json('chats'))->firstWhere('type', 'object');
        $this->assertSame('112 ТП-14 Северная', $item['title']);
        $this->assertSame(0, $item['unread_count']);
        $this->assertSame('Кабель привезли', $item['last_message']['text']);
        $chatId = $item['chat_id'];

        // Комментарий из табеля: событие и push отслеживающим
        $this->actingAsStaff($this->peer);
        $this->postJson('/api/timesheet/comment', ['timesheet_contract_id' => $yesterday, 'message' => 'Начали монтаж'])->assertCreated();
        Event::assertDispatched(MessengerEvent::class, fn (MessengerEvent $event) => $event->eventName === 'message.created'
            && $event->payload['chat_id'] === $chatId
            && in_array($this->me->id, $event->recipientStaffIds, true));
        $this->assertPushSent('ExponentPushToken[me]', '112 ТП-14 Северная', 'Петров А.: Начали монтаж');

        $this->actingAsStaff($this->me);
        $this->assertSame(1, collect($this->getJson('/api/messenger/chats')->json('chats'))->firstWhere('chat_id', $chatId)['unread_count']);

        // Сообщение из «Чатов» становится комментарием табеля на сегодняшний день
        $sent = $this->postJson('/api/messenger/message', ['chat_id' => $chatId, 'message' => 'Фото щита скинул'])
            ->assertCreated()
            ->assertJsonPath('context.day', today()->toDateString())
            ->assertJsonMissingPath('context.team');
        $this->assertSame($today, TimesheetChat::find($sent->json('id'))->timesheet_contract_id);

        // Лента объекта — все дни; окно вокруг сообщения
        $page = $this->postJson('/api/messenger/chat/messages', ['chat_id' => $chatId, 'limit' => 2, 'around_id' => $old->id])->assertOk();
        $this->assertSame($old->id, $page->json('messages.0.id'));
        $this->assertTrue($page->json('has_more_newer'));

        // Просмотр в табеле отмечает объект прочитанным
        $this->postJson('/api/messenger/object/read', ['contract_id' => $contractId])->assertOk()->assertJsonPath('chat_id', $chatId);
        $this->assertSame(0, collect($this->getJson('/api/messenger/chats')->json('chats'))->firstWhere('chat_id', $chatId)['unread_count']);

        // Без права на комментарии писать в чат объекта нельзя
        $this->actingAsStaff($this->outsider);
        $this->postJson('/api/messenger/message', ['chat_id' => $chatId, 'message' => 'Привет'])->assertForbidden();
    }

    public function test_object_chat_members_are_masters_assigned_in_timesheet(): void
    {
        $contractId = $this->contract('114', 'КТП-3 Лесная');
        // Давнее назначение тоже считается — без окна в 30 дней
        $this->assignToTeam($contractId, $this->peer, today()->subDays(200)->toDateString());
        $current = $this->assignToTeam($contractId, $this->me, today()->toDateString());
        $this->comment($current, $this->third, 'Комментарий не назначенного');

        $this->actingAsStaff($this->me);
        $chatId = collect($this->getJson('/api/messenger/chats')->assertOk()->json('chats'))->firstWhere('type', 'object')['chat_id'];

        $info = $this->getJson('/api/messenger/chat/info?chat_id=' . $chatId)->assertOk()
            ->assertJsonPath('is_member', true)
            ->assertJsonPath('can_add_members', true)
            ->assertJsonPath('can_remove_members', false)
            ->assertJsonPath('members_count', 2);
        $this->assertEqualsCanonicalizing([$this->me->id, $this->peer->id], array_column($info->json('members'), 'id'));

        // Автор комментариев без назначения и тот, кто открыл объект в табеле, участниками не становятся
        $this->actingAsStaff($this->third);
        $this->postJson('/api/messenger/object/read', ['contract_id' => $contractId])->assertOk();
        $this->postJson('/api/messenger/object/open', ['contract_id' => $contractId])->assertOk()->assertJsonPath('chat_id', $chatId);
        $this->assertNull(collect($this->getJson('/api/messenger/chats')->json('chats'))->firstWhere('chat_id', $chatId));
        $this->getJson('/api/messenger/chat/info?chat_id=' . $chatId)->assertOk()->assertJsonPath('is_member', false);
    }

    public function test_object_chat_members_can_be_added_removed_and_return_on_new_assignment(): void
    {
        $manager = ['mobile-app-can-create-contract:site'];
        $contractId = $this->contract('116', 'ТП-21 Озёрная');
        $this->assignToTeam($contractId, $this->me, today()->toDateString());
        $peerAssignment = $this->assignToTeam($contractId, $this->peer, today()->toDateString());
        $this->comment($peerAssignment, $this->peer, 'Начали');
        $chat = MessengerChat::forObject($contractId);

        // Участник без права ставить объекты добавлять может, убирать — нет
        $this->actingAsStaff($this->peer, []);
        $this->postJson('/api/messenger/object/members/add', ['chat_id' => $chat->id, 'member_ids' => [$this->third->id]])
            ->assertOk()->assertJsonPath('added', [$this->third->id]);
        $this->postJson('/api/messenger/object/members/remove', ['chat_id' => $chat->id, 'staff_id' => $this->me->id])->assertForbidden();
        $this->actingAsStaff($this->third, []);
        $this->assertNotNull(collect($this->getJson('/api/messenger/chats')->json('chats'))->firstWhere('chat_id', $chat->id));

        // Тот, кто ставит объекты, убирает участника — табель не возвращает его в чат
        $this->actingAsStaff($this->me, $manager);
        $this->getJson('/api/messenger/chat/info?chat_id=' . $chat->id)->assertJsonPath('can_remove_members', true);
        $this->postJson('/api/messenger/object/members/remove', ['chat_id' => $chat->id, 'staff_id' => $this->peer->id])
            ->assertOk()->assertJsonPath('removed', true);
        Event::assertDispatched(MessengerEvent::class, fn (MessengerEvent $event) => $event->eventName === 'chat.updated'
            && $event->recipientStaffIds === [$this->peer->id] && ($event->payload['removed'] ?? false));

        $this->actingAsStaff($this->peer, []);
        $this->assertNull(collect($this->getJson('/api/messenger/chats')->json('chats'))->firstWhere('chat_id', $chat->id));
        $this->actingAsStaff($this->me, $manager);
        $this->getJson('/api/messenger/chat/info?chat_id=' . $chat->id)->assertOk();
        $this->assertNotContains($this->peer->id, $chat->memberIds());

        // Объект снова поставили бригаде Петрова — он возвращается в чат
        $this->travel(2)->minutes();
        $this->assignToTeam($contractId, $this->peer, today()->addDay()->toDateString());
        $this->actingAsStaff($this->peer, []);
        $this->assertNotNull(collect($this->getJson('/api/messenger/chats')->json('chats'))->firstWhere('chat_id', $chat->id));

        // Выйти может любой участник
        $this->actingAsStaff($this->third, []);
        $this->postJson('/api/messenger/object/leave', ['chat_id' => $chat->id])->assertOk()->assertJsonPath('removed', true);
        $this->assertNull(collect($this->getJson('/api/messenger/chats')->json('chats'))->firstWhere('chat_id', $chat->id));
    }

    public function test_mute_objects_setting_silences_all_object_chats_and_new_ones(): void
    {
        $first = $this->contract('117', 'РП-5 Речная');
        $mine = $this->assignToTeam($first, $this->me, today()->toDateString());
        $this->comment($mine, $this->peer, 'Первый объект');
        $direct = MessengerChat::getOrCreate($this->me->id, $this->peer->id);
        $this->message($direct, $this->peer, 'Личное');
        $this->token($this->me, 'ExponentPushToken[me]');

        $this->actingAsStaff($this->me);
        $this->getJson('/api/messenger/settings')->assertOk()->assertJsonPath('mute_objects', false);
        $this->getJson('/api/messenger/chats')->assertOk();
        $this->postJson('/api/messenger/settings', ['mute_objects' => true])->assertOk()->assertJsonPath('mute_objects', true);
        $this->getJson('/api/messenger/settings')->assertJsonPath('mute_objects', true);

        // Новый объект приходит уже без звука, личный чат звук не теряет
        $second = $this->contract('118', 'ТП-30 Новая');
        $secondAssignment = $this->assignToTeam($second, $this->me, today()->toDateString());
        $this->comment($secondAssignment, $this->peer, 'Второй объект');
        $chats = collect($this->getJson('/api/messenger/chats')->json('chats'));
        $this->assertTrue($chats->where('type', 'object')->every(fn (array $chat) => $chat['muted']));
        $this->assertFalse($chats->firstWhere('type', 'direct')['muted']);

        // Комментарий в объекте без звука — без push
        $this->actingAsStaff($this->peer, ['mobile-app-can-create-comment:site']);
        $this->postJson('/api/timesheet/comment', ['timesheet_contract_id' => $secondAssignment, 'message' => 'Тихо'])->assertCreated();
        Http::assertNotSent(fn (HttpRequest $request) => str_contains($request->url(), 'exp.host') && ($request[0]['to'] ?? null) === 'ExponentPushToken[me]');

        // Выключение настройки возвращает звук всем объектам
        $this->actingAsStaff($this->me);
        $this->postJson('/api/messenger/settings', ['mute_objects' => false])->assertOk();
        $this->assertTrue(collect($this->getJson('/api/messenger/chats')->json('chats'))->where('type', 'object')->every(fn (array $chat) => !$chat['muted']));
    }

    public function test_one_reaction_per_person_new_one_replaces_previous(): void
    {
        $direct = MessengerChat::getOrCreate($this->me->id, $this->peer->id);
        $message = $this->message($direct, $this->peer, 'Щит смонтирован');
        $contractId = $this->contract('119', 'КТП-7 Полевая');
        $comment = $this->comment($this->assignToTeam($contractId, $this->peer, today()->toDateString()), $this->peer, 'Начали');

        $this->actingAsStaff($this->me);
        $userId = User::where('staff_id', $this->me->id)->value('id');
        $mine = fn (array $reactions) => array_values(array_filter($reactions, fn ($reaction) => (int) $reaction['user_id'] === (int) $userId));

        // Сообщение: вторая реакция заменяет первую, повтор снимает
        $this->postJson('/api/messenger/message/reaction', ['message_id' => $message->id, 'emoji' => '👍'])->assertOk();
        $reactions = $this->postJson('/api/messenger/message/reaction', ['message_id' => $message->id, 'emoji' => '🔥'])->assertOk()->json('reactions');
        $this->assertSame([['user_id' => $userId, 'emoji' => '🔥']], $mine($reactions));
        $reactions = $this->postJson('/api/messenger/message/reaction', ['message_id' => $message->id, 'emoji' => '🔥'])->assertOk()->json('reactions');
        $this->assertSame([], $mine($reactions));

        // Комментарий табеля: то же правило; реакции других людей не трогаются
        $comment->update(['reactions' => [['user_id' => 999999, 'emoji' => '❤️']]]);
        $this->postJson('/api/timesheet/comment/reaction', ['comment_id' => $comment->id, 'emoji' => '👍'])->assertOk();
        $reactions = $this->postJson('/api/timesheet/comment/reaction', ['comment_id' => $comment->id, 'emoji' => '✅'])->assertOk()->json('reactions');
        $this->assertCount(2, $reactions);
        $this->assertSame([['user_id' => $userId, 'emoji' => '✅']], $mine($reactions));
    }

    public function test_timesheet_comment_can_be_deleted_only_by_author(): void
    {
        $contractId = $this->contract('113', 'ТП-9 Южная');
        $timesheetContractId = $this->assignToTeam($contractId, $this->peer, today()->toDateString());
        $comment = $this->comment($timesheetContractId, $this->peer, 'Мой комментарий');

        $this->actingAsStaff($this->me);
        $this->deleteJson('/api/timesheet/comment/' . $comment->id)->assertForbidden();
        $this->assertNotNull($comment->fresh());

        $this->actingAsStaff($this->peer);
        $this->deleteJson('/api/timesheet/comment/' . $comment->id)->assertOk();
        $this->assertNull($comment->fresh());
    }

    // --- Помощники ---

    private function group(array $members, string $title = 'Бригада'): MessengerChat
    {
        $chat = MessengerChat::create(['type' => MessengerChat::TYPE_GROUP, 'title' => $title, 'created_by' => $this->me->id]);
        $chat->ensureMember($this->me->id, null, MessengerChatMember::ROLE_ADMIN);
        foreach ($members as $member) {
            $chat->ensureMember($member->id);
        }

        return $chat;
    }

    private function createStaff(string $fname, string $sname): Staff
    {
        return Staff::find(DB::table('staff')->insertGetId(['fname' => $fname, 'sname' => $sname]));
    }

    private ?array $permissions = null;

    private function givePermissions(array $names): void
    {
        $this->permissions = $names;
    }

    /**
     * @param string[]|null $permissions права именно этого сотрудника (иначе — общие из givePermissions)
     */
    private function actingAsStaff(Staff $staff, ?array $permissions = null): void
    {
        $user = User::firstOrCreate(['staff_id' => $staff->id], [
            'email' => "groups{$staff->id}@example.com",
            'password' => 'correct-horse-42',
        ]);

        $names = $permissions ?? ($staff->id !== $this->outsider->id ? $this->permissions : null);
        if ($names !== null) {
            foreach ($names as $name) {
                Permission::findOrCreate($name, 'site');
            }
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $user->syncPermissions($names);
        }

        Sanctum::actingAs($user);
    }

    private function message(MessengerChat $chat, Staff $from, string $text): MessengerMessage
    {
        return MessengerMessage::create(['chat_id' => $chat->id, 'from_id' => $from->id, 'message' => $text]);
    }

    private function contract(string $number, string $title): int
    {
        // В схеме из миграций репозитория last_id обязателен, в схеме production — нет
        return DB::table('contracts')->insertGetId([
            'object_number' => $number,
            'title' => $title,
            'created_at' => now(),
            'updated_at' => now(),
        ] + (Schema::hasColumn('contracts', 'last_id') ? ['last_id' => 0] : []));
    }

    private function assignToTeam(int $contractId, Staff $master, string $day): int
    {
        $teamId = DB::table('timesheet_teams')->insertGetId(['staff_id' => $master->id, 'day' => $day, 'created_at' => now(), 'updated_at' => now()]);

        return DB::table('timesheet_contracts')->insertGetId(['team_id' => $teamId, 'contract_id' => $contractId, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function comment(int $timesheetContractId, Staff $from, string $text): TimesheetChat
    {
        return TimesheetChat::create(['timesheet_contract_id' => $timesheetContractId, 'from_id' => $from->id, 'message' => $text]);
    }

    private function token(Staff $staff, string $token): void
    {
        StaffPushToken::create([
            'staff_id' => $staff->id,
            'installation_id' => $token,
            'token' => $token,
            'platform' => 'ios',
            'token_type' => 'expo',
        ]);
    }

    private function assertPushSent(string $token, string $title, string $body): void
    {
        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), 'exp.host')
            && ($request[0]['to'] ?? null) === $token
            && $request[0]['title'] === $title
            && $request[0]['body'] === $body
            && $request[0]['data']['type'] === 'message');
    }
}
