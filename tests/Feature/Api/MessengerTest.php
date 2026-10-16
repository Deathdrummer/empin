<?php

namespace Tests\Feature\Api;

use App\Events\MessengerEvent;
use App\Models\MessengerChat;
use App\Models\MessengerMessage;
use App\Models\Staff;
use App\Models\StaffPushToken;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Мессенджер мобильного приложения.
 * Тест работает на уже мигрированной базе и откатывает свои записи (DatabaseTransactions).
 */
class MessengerTest extends TestCase
{
    use DatabaseTransactions;

    private Staff $me;
    private Staff $peer;
    private Staff $outsider;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([MessengerEvent::class]);
        Http::fake(['exp.host/*' => Http::response(['data' => [['status' => 'ok', 'id' => 'ticket']]])]);

        $this->me = $this->createStaff('Я', 'Сотрудник');
        $this->peer = $this->createStaff('Артём', 'Самсонов');
        $this->outsider = $this->createStaff('Чужой', 'Посторонний');
    }

    public function test_outsider_cannot_read_write_or_react_in_foreign_chat(): void
    {
        $chat = MessengerChat::getOrCreate($this->me->id, $this->peer->id);
        $message = $this->message($chat, $this->me, 'Секрет');
        $this->actingAsStaff($this->outsider);

        $this->postJson('/api/messenger/chat/messages', ['chat_id' => $chat->id])->assertNotFound();
        $this->postJson('/api/messenger/message', ['chat_id' => $chat->id, 'message' => 'Привет'])->assertNotFound();
        $this->postJson('/api/messenger/message/reaction', ['message_id' => $message->id, 'emoji' => '👍'])->assertNotFound();
        $this->postJson('/api/messenger/chat/read', ['chat_id' => $chat->id, 'message_id' => $message->id])->assertNotFound();
        $this->postJson('/api/messenger/chat/typing', ['chat_id' => $chat->id])->assertNotFound();

        $this->assertSame(1, MessengerMessage::where('chat_id', $chat->id)->count());
        $this->assertSame([], $message->fresh()->reactions ?? []);
        Event::assertNotDispatched(MessengerEvent::class);
    }

    public function test_reply_must_belong_to_the_same_chat(): void
    {
        $chat = MessengerChat::getOrCreate($this->me->id, $this->peer->id);
        $otherChat = MessengerChat::getOrCreate($this->peer->id, $this->outsider->id);
        $foreign = $this->message($otherChat, $this->peer, 'Из другого чата');
        $this->actingAsStaff($this->me);

        $this->postJson('/api/messenger/message', [
            'chat_id' => $chat->id,
            'message' => 'Ответ',
            'reply_to_id' => $foreign->id,
        ])->assertStatus(422);

        $own = $this->message($chat, $this->peer, 'Вопрос');
        $this->postJson('/api/messenger/message', [
            'chat_id' => $chat->id,
            'message' => 'Ответ',
            'reply_to_id' => $own->id,
        ])->assertCreated()->assertJsonPath('reply_to.message', 'Вопрос')
            ->assertJsonPath('reply_to.from.sname', 'Самсонов');
    }

    public function test_chat_cannot_be_created_with_self_or_unknown_staff(): void
    {
        $this->actingAsStaff($this->me);

        $this->postJson('/api/messenger/chat', ['participant_id' => $this->me->id])->assertStatus(422);
        $this->postJson('/api/messenger/chat', ['participant_id' => 999999999])->assertStatus(422);
        $this->postJson('/api/messenger/chat', ['participant_id' => $this->peer->id])->assertOk()->assertJsonStructure(['chat_id']);
    }

    public function test_chat_list_shows_last_message_unread_count_and_recent_first(): void
    {
        $older = MessengerChat::getOrCreate($this->me->id, $this->outsider->id);
        $this->message($older, $this->outsider, 'Старое');
        $recent = MessengerChat::getOrCreate($this->me->id, $this->peer->id);
        $this->message($recent, $this->me, 'Пришлите акт');
        $this->message($recent, $this->peer, 'Отправил');
        $this->message($recent, $this->peer, null, [['path' => '/storage/x.jpg', 'type' => 'image']]);
        MessengerChat::getOrCreate($this->me->id, $this->createStaff('Пустой', 'Чат')->id);
        $this->actingAsStaff($this->me);

        $response = $this->getJson('/api/messenger/chats')->assertOk();

        $response->assertJsonCount(2, 'chats')
            ->assertJsonPath('chats.0.chat_id', $recent->id)
            ->assertJsonPath('chats.0.participant.sname', 'Самсонов')
            ->assertJsonPath('chats.0.last_message.text', 'Фото')
            ->assertJsonPath('chats.0.last_message.type', 'image')
            ->assertJsonPath('chats.0.last_message.self', false)
            ->assertJsonPath('chats.0.unread_count', 2)
            ->assertJsonPath('chats.1.chat_id', $older->id)
            ->assertJsonPath('chats.1.unread_count', 1)
            ->assertJsonPath('total_unread', 3);
    }

    public function test_mark_read_moves_forward_only_and_notifies_peer(): void
    {
        $chat = MessengerChat::getOrCreate($this->me->id, $this->peer->id);
        $first = $this->message($chat, $this->peer, 'Раз');
        $second = $this->message($chat, $this->peer, 'Два');
        $this->actingAsStaff($this->me);

        $this->postJson('/api/messenger/chat/read', ['chat_id' => $chat->id, 'message_id' => $second->id])
            ->assertOk()
            ->assertJsonPath('last_read_id', $second->id)
            ->assertJsonPath('total_unread', 0);
        Event::assertDispatched(MessengerEvent::class, fn (MessengerEvent $event) => $event->recipientStaffId === $this->peer->id
            && $event->eventName === 'chat.read'
            && $event->payload['last_read_id'] === $second->id);

        $this->postJson('/api/messenger/chat/read', ['chat_id' => $chat->id, 'message_id' => $first->id])
            ->assertOk()
            ->assertJsonPath('last_read_id', $second->id);
    }

    public function test_peer_read_state_is_visible_to_sender(): void
    {
        $chat = MessengerChat::getOrCreate($this->me->id, $this->peer->id);
        $sent = $this->message($chat, $this->me, 'Посмотрите');
        $chat->markReadBy($this->peer->id, $sent->id);
        $this->actingAsStaff($this->me);

        $this->postJson('/api/messenger/chat/messages', ['chat_id' => $chat->id])
            ->assertOk()
            ->assertJsonPath('peer_last_read_id', $sent->id)
            ->assertJsonPath('messages.0.from_id', $this->me->id);
        $this->getJson('/api/messenger/chats')->assertJsonPath('chats.0.last_message.read', true);
    }

    public function test_history_pages_and_legacy_full_history(): void
    {
        $chat = MessengerChat::getOrCreate($this->me->id, $this->peer->id);
        $ids = [];
        for ($i = 1; $i <= 5; $i++) {
            $ids[] = $this->message($chat, $i % 2 ? $this->me : $this->peer, "Сообщение {$i}")->id;
        }
        $this->actingAsStaff($this->me);

        $latest = $this->postJson('/api/messenger/chat/messages', ['chat_id' => $chat->id, 'limit' => 2])->assertOk();
        $this->assertSame([$ids[3], $ids[4]], array_column($latest->json('messages'), 'id'));
        $this->assertTrue($latest->json('has_more'));

        $older = $this->postJson('/api/messenger/chat/messages', ['chat_id' => $chat->id, 'limit' => 3, 'before_id' => $ids[3]])->assertOk();
        $this->assertSame([$ids[0], $ids[1], $ids[2]], array_column($older->json('messages'), 'id'));
        $this->assertFalse($older->json('has_more'));

        $newer = $this->postJson('/api/messenger/chat/messages', ['chat_id' => $chat->id, 'after_id' => $ids[2]])->assertOk();
        $this->assertSame([$ids[3], $ids[4]], array_column($newer->json('messages'), 'id'));

        $legacy = $this->postJson('/api/messenger/chat/messages', ['chat_id' => $chat->id])->assertOk();
        $this->assertCount(5, $legacy->json('messages'));
    }

    public function test_new_message_reaches_peer_in_realtime_and_by_push_with_unread_badge(): void
    {
        $chat = MessengerChat::getOrCreate($this->me->id, $this->peer->id);
        $this->token($this->peer, 'ExponentPushToken[peer-phone]', 'ios');
        $this->token($this->me, 'ExponentPushToken[my-phone]', 'ios');
        $this->actingAsStaff($this->me);

        $id = $this->postJson('/api/messenger/message', ['chat_id' => $chat->id, 'message' => 'Акт готов'])
            ->assertCreated()
            ->assertJsonPath('self', true)
            ->json('id');

        Event::assertDispatched(MessengerEvent::class, fn (MessengerEvent $event) => $event->recipientStaffId === $this->peer->id
            && $event->eventName === 'message.created'
            && $event->payload['message']['id'] === $id
            && $event->payload['message']['self'] === false
            && $event->payload['message']['from_id'] === $this->me->id);
        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), 'exp.host')
            && $request[0]['to'] === 'ExponentPushToken[peer-phone]'
            && $request[0]['title'] === 'Сотрудник Я'
            && $request[0]['body'] === 'Акт готов'
            && $request[0]['badge'] === 1
            && $request[0]['data']['type'] === 'message'
            && $request[0]['data']['chat_id'] === $chat->id);
        Http::assertNotSent(fn (HttpRequest $request) => ($request[0]['to'] ?? null) === 'ExponentPushToken[my-phone]');

        // Отправитель свой чат прочитал
        $this->assertSame($id, $chat->fresh()->lastReadIdFor($this->me->id));
    }

    public function test_typing_notifies_only_the_peer(): void
    {
        $chat = MessengerChat::getOrCreate($this->me->id, $this->peer->id);
        $this->actingAsStaff($this->me);

        $this->postJson('/api/messenger/chat/typing', ['chat_id' => $chat->id])->assertOk();

        Event::assertDispatched(MessengerEvent::class, fn (MessengerEvent $event) => $event->recipientStaffId === $this->peer->id
            && $event->eventName === 'chat.typing'
            && $event->payload === ['chat_id' => $chat->id, 'staff_id' => $this->me->id]);
    }

    public function test_too_long_message_event_carries_only_its_id(): void
    {
        $chat = MessengerChat::getOrCreate($this->me->id, $this->peer->id);
        $this->actingAsStaff($this->me);

        $id = $this->postJson('/api/messenger/message', ['chat_id' => $chat->id, 'message' => str_repeat('Длинный текст ', 1500)])
            ->assertCreated()
            ->json('id');

        Event::assertDispatched(MessengerEvent::class, fn (MessengerEvent $event) => $event->eventName === 'message.created'
            && $event->payload === ['chat_id' => $chat->id, 'message_id' => $id, 'truncated' => true]);
    }

    private function createStaff(string $fname, string $sname): Staff
    {
        return Staff::find(DB::table('staff')->insertGetId(['fname' => $fname, 'sname' => $sname]));
    }

    private function actingAsStaff(Staff $staff): void
    {
        Sanctum::actingAs(User::create([
            'email' => "messenger{$staff->id}@example.com",
            'password' => 'correct-horse-42',
            'staff_id' => $staff->id,
        ]));
    }

    private function message(MessengerChat $chat, Staff $from, ?string $text, ?array $media = null): MessengerMessage
    {
        return MessengerMessage::create([
            'chat_id' => $chat->id,
            'from_id' => $from->id,
            'message' => $text,
            'media' => $media,
        ]);
    }

    private function token(Staff $staff, string $token, string $platform): void
    {
        StaffPushToken::create([
            'staff_id' => $staff->id,
            'installation_id' => $token,
            'token' => $token,
            'platform' => $platform,
            'token_type' => 'expo',
        ]);
    }
}
