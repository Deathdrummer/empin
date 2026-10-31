<?php

namespace Tests\Feature\Api;

use App\Events\MessengerEvent;
use App\Models\MessengerChat;
use App\Models\Staff;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Возможности мессенджера на уровне Telegram (план PLAN_messenger_full_2026-10-30.md).
 */
class MessengerTelegramFeaturesTest extends TestCase
{
    use DatabaseTransactions;
    use MessengerTestHelpers;

    private Staff $me;
    private Staff $peer;
    private Staff $third;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([MessengerEvent::class]);
        Http::fake(['exp.host/*' => Http::response(['data' => [['status' => 'ok', 'id' => 'ticket']]])]);
        Cache::flush();

        $this->me = $this->createStaff('Сергей', 'Иванов');
        $this->peer = $this->createStaff('Алексей', 'Петров');
        $this->third = $this->createStaff('Артём', 'Самсонов');
    }

    // --- A2. Непрочитанные и упоминания ---

    public function test_opening_chat_returns_first_unread_and_my_unread_mentions(): void
    {
        $group = $this->makeGroup($this->me, [$this->peer, $this->third]);
        $read = $this->say($group, $this->peer, 'Утро');
        $group->markReadBy($this->me->id, $read->id);
        $this->say($group, $this->me, 'Моё — не непрочитанное');
        $first = $this->say($group, $this->peer, 'Первое непрочитанное');
        $this->say($group, $this->third, 'Системное', ['type' => 'system']);
        $mentionMe = $this->say($group, $this->third, '@Иванов С. глянь', ['mentions' => [$this->me->id]]);
        $this->say($group, $this->third, '@Петров А. тебе', ['mentions' => [$this->peer->id]]);
        $mentionMe2 = $this->say($group, $this->peer, '@Иванов С. и ещё', ['mentions' => [$this->me->id, $this->third->id]]);

        $this->signIn($this->me);
        $unread = $this->postJson('/api/messenger/chat/messages', ['chat_id' => $group->id, 'limit' => 30])->assertOk()->json('unread');
        $this->assertSame([
            'last_read_id' => $read->id,
            'first_id'     => $first->id,
            'count'        => 4,
            'mention_ids'  => [$mentionMe->id, $mentionMe2->id],
        ], $unread);
        // Совпадает со счётчиком в списке чатов
        $this->assertSame(4, $this->chatItem($group->id)['unread_count']);

        // Догрузка истории — без пересчёта; всё прочитано — непрочитанных нет
        $this->assertNull($this->postJson('/api/messenger/chat/messages', ['chat_id' => $group->id, 'limit' => 30, 'before_id' => $first->id])->json('unread'));
        $group->markReadBy($this->me->id, $mentionMe2->id);
        $this->assertSame(['last_read_id' => $mentionMe2->id, 'first_id' => null, 'count' => 0, 'mention_ids' => []],
            $this->postJson('/api/messenger/chat/messages', ['chat_id' => $group->id, 'limit' => 30])->json('unread'));
    }

    public function test_object_chat_unread_counts_comments_and_non_member_has_none(): void
    {
        $contractId = $this->makeContract('112', 'ТП-14 Северная');
        $placement = $this->assignContract($contractId, $this->me, today()->toDateString());
        $old = $this->addComment($placement, $this->peer, 'Кабель привезли');
        $chat = MessengerChat::forObject($contractId);
        $chat->ensureMember($this->me->id, $old->id);
        $new = $this->addComment($placement, $this->peer, 'Начали монтаж');
        $this->addComment($placement, $this->me, 'Моё');

        $this->signIn($this->me);
        $unread = $this->postJson('/api/messenger/chat/messages', ['chat_id' => $chat->id, 'limit' => 30])->assertOk()->json('unread');
        $this->assertSame(['last_read_id' => $old->id, 'first_id' => $new->id, 'count' => 1, 'mention_ids' => []], $unread);

        // Открыл объект из поиска, не будучи участником: непрочитанных нет
        $this->signIn($this->third);
        $response = $this->postJson('/api/messenger/chat/messages', ['chat_id' => $chat->id, 'limit' => 30])->assertOk();
        $this->assertArrayHasKey('unread', $response->json());
        $this->assertNull($response->json('unread'));
    }

    // --- A3. Медиа, файлы, голосовые, ссылки ---

    public function test_chat_media_lists_photos_files_voice_and_links_page_by_page(): void
    {
        $group = $this->makeGroup($this->me, [$this->peer]);
        $item = fn (string $name, string $type, string $mime) => ['path' => "/storage/messenger/messages/{$name}", 'type' => $type, 'mime_type' => $mime, 'size' => 10, 'name' => $name];

        $album = $this->say($group, $this->peer, 'Щит', ['media' => [$item('a.jpg', 'image', 'image/jpeg'), $item('b.mp4', 'video', 'video/mp4')]]);
        $doc = $this->say($group, $this->me, '', ['media' => [$item('акт.pdf', 'document', 'application/pdf')]]);
        // Старый формат — объект; голосовое с Android раньше сохранялось как «видео»
        $voice = $this->say($group, $this->peer, '', ['media' => $item('Голосовое сообщение.m4a', 'video', 'video/mp4')]);
        $links = $this->say($group, $this->peer, 'Схема: **https://empin-pro.ru/docs/12** и сайт эмпин.рф. Почта ivanov@empin-pro.ru');
        $this->say($group, $this->peer, 'Без ссылок, т.к. 1.5 кВ');
        $this->say($group, $this->peer, 'Система', ['type' => 'system', 'media' => [$item('x.jpg', 'image', 'image/jpeg')]]);

        $this->signIn($this->me);
        $get = fn (array $params) => $this->getJson('/api/messenger/chat/media?' . http_build_query(['chat_id' => $group->id] + $params))->assertOk()->json();

        $media = $get(['kind' => 'media']);
        $this->assertSame([[$album->id, 'a.jpg'], [$album->id, 'b.mp4']], array_map(fn ($i) => [$i['message_id'], $i['media']['name']], $media['items']));
        $this->assertSame('Петров А.', $media['items'][0]['from']['name']);
        $this->assertNull($media['next_before_id']);

        $this->assertSame([$doc->id], array_column($get(['kind' => 'files'])['items'], 'message_id'));
        $voiceItems = $get(['kind' => 'voice'])['items'];
        $this->assertSame([$voice->id], array_column($voiceItems, 'message_id'));
        $this->assertSame('audio', $voiceItems[0]['media']['type']);

        $linkItems = $get(['kind' => 'links'])['items'];
        $this->assertSame(['https://empin-pro.ru/docs/12', 'эмпин.рф'], array_column($linkItems, 'url'));
        $this->assertSame($links->id, $linkItems[0]['message_id']);
        $this->assertStringNotContainsString('**', $linkItems[0]['text']);

        // Постранично: альбом не делится, следующая страница — старше
        $page = $get(['kind' => 'media', 'limit' => 1]);
        $this->assertCount(2, $page['items']);
        $this->assertSame($album->id, $page['next_before_id']);
        $this->assertSame([], $get(['kind' => 'media', 'limit' => 1, 'before_id' => $page['next_before_id']])['items']);

        // Чужой чат — как несуществующий; неизвестный вид — ошибка проверки
        $this->signIn($this->third);
        $this->getJson('/api/messenger/chat/media?chat_id=' . $group->id . '&kind=media')->assertNotFound();
        $this->signIn($this->me);
        $this->getJson('/api/messenger/chat/media?chat_id=' . $group->id . '&kind=stickers')->assertStatus(422);
    }

    public function test_object_chat_media_comes_from_timesheet_comments(): void
    {
        $contractId = $this->makeContract('112', 'ТП-14 Северная');
        $placement = $this->assignContract($contractId, $this->me, today()->toDateString());
        $photo = $this->addComment($placement, $this->peer, 'Фото щита', ['media' => [['path' => '/storage/timesheet/comments/s.jpg', 'type' => 'image', 'mime_type' => 'image/jpeg', 'size' => 5, 'name' => 's.jpg']]]);
        $chat = MessengerChat::forObject($contractId);
        $chat->ensureMember($this->me->id);

        $this->signIn($this->me);
        $items = $this->getJson("/api/messenger/chat/media?chat_id={$chat->id}&kind=media")->assertOk()->json('items');
        $this->assertSame([$photo->id], array_column($items, 'message_id'));
    }

    // --- A4/A5. «Волна» и длительность голосового, кадр-превью видео ---

    public function test_voice_waveform_duration_and_video_thumbnail_are_stored_copied_and_deleted(): void
    {
        Storage::fake('public');
        $direct = MessengerChat::getOrCreate($this->me->id, $this->peer->id);
        $group = $this->makeGroup($this->me, [$this->peer]);

        $this->signIn($this->me, ['mobile-app-can-delete-comment:site']);
        $sent = $this->post('/api/messenger/message', [
            'chat_id' => $direct->id,
            'message' => '',
            'media' => [
                UploadedFile::fake()->create('Голосовое сообщение.m4a', 20, 'audio/mp4'),
                UploadedFile::fake()->create('щит.mp4', 50, 'video/mp4'),
            ],
            'media_meta' => [
                // Столбики за пределами 0…31 обрезаются, нечисловые выбрасываются, «волна» не длиннее 100
                json_encode(['waveform' => array_merge([-5, 40, 'x', 12.6], array_fill(0, 120, 3)), 'duration' => 7.04, 'evil' => '<script>']),
                json_encode(['duration' => 99999, 'width' => 1080, 'height' => 1920]),
            ],
            'media_thumb' => [1 => UploadedFile::fake()->createWithContent('кадр.jpg', $this->tinyJpeg())],
        ], ['Accept' => 'application/json'])->assertCreated();

        [$voice, $video] = $sent->json('data.media') ?? $sent->json('media');
        $this->assertSame([0, 31, 13], array_slice($voice['waveform'], 0, 3));
        $this->assertCount(100, $voice['waveform']);
        $this->assertSame(7.0, (float) $voice['duration']);
        $this->assertArrayNotHasKey('evil', $voice);
        $this->assertArrayNotHasKey('duration', $video);
        $this->assertSame([1080, 1920], [$video['width'], $video['height']]);
        $this->assertStringStartsWith('/storage/messenger/messages/thumbs/', $video['thumbnail']);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $video['thumbnail']));
        $this->assertArrayNotHasKey('thumbnail', $voice);

        // Пересылка — свой кадр у копии
        $messageId = $sent->json('data.id') ?? $sent->json('id');
        $this->postJson('/api/messenger/forward', ['chat_id' => $direct->id, 'message_ids' => [$messageId], 'target_chat_ids' => [$group->id]])->assertOk();
        $copy = $group->messages()->latest('id')->first()->media[1];
        $this->assertNotSame($video['thumbnail'], $copy['thumbnail']);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $copy['thumbnail']));

        // Удаление сообщения удаляет и кадр
        $this->deleteJson("/api/messenger/message/{$messageId}?chat_id={$direct->id}")->assertOk();
        Storage::disk('public')->assertMissing(str_replace('/storage/', '', $video['thumbnail']));
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $copy['thumbnail']));
    }

    // --- A6. «Удалить у меня» и «Прочитали» ---

    public function test_message_hidden_for_me_disappears_only_for_me_everywhere(): void
    {
        $direct = MessengerChat::getOrCreate($this->me->id, $this->peer->id);
        $foreignChat = MessengerChat::getOrCreate($this->peer->id, $this->third->id);
        $first = $this->say($direct, $this->peer, 'Схема https://empin-pro.ru/a щита');
        $read = $this->say($direct, $this->me, 'Принял');
        $direct->markReadBy($this->me->id, $read->id);
        $unread = $this->say($direct, $this->peer, 'Ещё схема https://empin-pro.ru/b щита');
        $foreign = $this->say($foreignChat, $this->peer, 'Чужое');
        $first->forceFill(['pinned_at' => now(), 'pinned_by' => $this->peer->id])->save();

        $this->signIn($this->me);
        $this->assertSame(1, $this->chatItem($direct->id)['unread_count']);

        // Сообщение чужого чата молча пропускается, повтор id не мешает
        $hidden = $this->postJson('/api/messenger/message/hide', [
            'chat_id' => $direct->id,
            'message_ids' => [$unread->id, $first->id, $foreign->id, $unread->id],
        ])->assertOk();
        $this->assertEqualsCanonicalizing([$first->id, $unread->id], $hidden->json('message_ids'));
        $this->assertSame(0, $hidden->json('total_unread'));
        Event::assertDispatched(MessengerEvent::class, fn (MessengerEvent $event) => $event->eventName === 'messages.hidden'
            && $event->recipientStaffIds === [$this->me->id]
            && $event->payload['chat_id'] === $direct->id);
        // Повторно — без ошибки
        $this->postJson('/api/messenger/message/hide', ['chat_id' => $direct->id, 'message_ids' => [$first->id]])->assertOk();

        // У меня их нет нигде: лента, список чатов, счётчик, поиск, ссылки, закреплённые
        $this->assertSame([$read->id], array_column($this->postJson('/api/messenger/chat/messages', ['chat_id' => $direct->id, 'limit' => 30])->json('messages'), 'id'));
        $item = $this->chatItem($direct->id);
        $this->assertSame($read->id, $item['last_message']['id']);
        $this->assertSame(0, $item['unread_count']);
        $this->assertSame([], $this->getJson('/api/messenger/search?q=' . urlencode('щита'))->assertOk()->json('messages'));
        $this->assertSame([], $this->getJson("/api/messenger/chat/media?chat_id={$direct->id}&kind=links")->json('items'));
        $this->assertSame([], $this->getJson("/api/messenger/chat/info?chat_id={$direct->id}")->assertOk()->json('pinned_messages'));

        // У собеседника всё на месте
        $this->signIn($this->peer);
        $this->assertCount(3, $this->postJson('/api/messenger/chat/messages', ['chat_id' => $direct->id, 'limit' => 30])->json('messages'));
        $this->assertSame($unread->id, $this->chatItem($direct->id)['last_message']['id']);
        $this->assertCount(2, $this->getJson('/api/messenger/search?q=' . urlencode('щита'))->json('messages'));
        $this->assertCount(2, $this->getJson("/api/messenger/chat/media?chat_id={$direct->id}&kind=links")->json('items'));
        $this->assertCount(1, $this->getJson("/api/messenger/chat/info?chat_id={$direct->id}")->json('pinned_messages'));

        // Чужой чат — как несуществующий
        $this->signIn($this->third);
        $this->postJson('/api/messenger/message/hide', ['chat_id' => $direct->id, 'message_ids' => [$first->id]])->assertNotFound();
    }

    public function test_object_chat_comment_hidden_for_me_and_readers_are_known(): void
    {
        $contractId = $this->makeContract('112', 'ТП-14 Северная');
        $placement = $this->assignContract($contractId, $this->me, today()->toDateString());
        $chat = MessengerChat::forObject($contractId);
        $chat->ensureMember($this->me->id);
        $chat->ensureMember($this->peer->id);
        $mine = $this->addComment($placement, $this->me, 'Щит смонтирован');
        $last = $this->addComment($placement, $this->peer, 'Принято, фото позже');
        // Мастер, добавленный табелем позже: граница прочитанного — последний комментарий, но он их не читал
        $chat->ensureMember($this->third->id, $last->id);

        $this->signIn($this->peer);
        $this->postJson('/api/messenger/chat/read', ['chat_id' => $chat->id, 'message_id' => $mine->id])->assertOk();
        // Автор узнаёт сразу: «Прочитали» у комментария объекта
        Event::assertDispatched(MessengerEvent::class, fn (MessengerEvent $event) => $event->eventName === 'chat.read'
            && in_array($this->me->id, $event->recipientStaffIds, true)
            && $event->payload['reader_id'] === $this->peer->id
            && $event->payload['last_read_id'] === $mine->id);

        $this->signIn($this->me);
        $page = $this->postJson('/api/messenger/chat/messages', ['chat_id' => $chat->id, 'limit' => 30])->assertOk()->json();
        $this->assertSame($mine->id, $page['members_read'][$this->peer->id]);
        $this->assertSame($last->id, $page['members_read'][$this->third->id]);
        $this->assertArrayNotHasKey($this->me->id, $page['members_read']);
        // Точка вступления: позже добавленный «получил» комментарии до неё, не читая; бывший с начала — null
        $this->assertSame($last->id, $page['members_joined'][$this->third->id]);
        $this->assertNull($page['members_joined'][$this->peer->id]);

        // «Удалить у меня» в объекте: только у меня, и в списке чатов последним становится предыдущий
        $this->postJson('/api/messenger/message/hide', ['chat_id' => $chat->id, 'message_ids' => [$last->id]])
            ->assertOk()
            ->assertJsonPath('message_ids', [$last->id]);
        $this->assertSame([$mine->id], array_column($this->postJson('/api/messenger/chat/messages', ['chat_id' => $chat->id, 'limit' => 30])->json('messages'), 'id'));
        $this->assertSame($mine->id, $this->chatItem($chat->id)['last_message']['id']);

        $this->signIn($this->peer);
        $this->assertCount(2, $this->postJson('/api/messenger/chat/messages', ['chat_id' => $chat->id, 'limit' => 30])->json('messages'));
        $this->assertSame($last->id, $this->chatItem($chat->id)['last_message']['id']);
    }

    public function test_master_added_by_timesheet_with_new_comment_has_not_read_it(): void
    {
        $contractId = $this->makeContract('112', 'ТП-14 Северная');
        $this->assignContract($contractId, $this->me, today()->toDateString());
        // Объект поставлен и бригаде Самсонова — в чат он попадёт при первом же комментарии
        $this->assignContract($contractId, $this->third, today()->toDateString());
        $chat = MessengerChat::forObject($contractId);
        $chat->ensureMember($this->me->id);

        $this->signIn($this->me, ['mobile-app-can-create-comment:site']);
        $sent = $this->postJson('/api/messenger/message', ['chat_id' => $chat->id, 'message' => 'Щит смонтирован'])->assertCreated();
        $commentId = $sent->json('data.id') ?? $sent->json('id');

        // Граница прочитанного у добавленного — этот комментарий, но прочтением это не считается
        $joined = $this->postJson('/api/messenger/chat/messages', ['chat_id' => $chat->id, 'limit' => 30])->json();
        $this->assertSame($commentId, $joined['members_read'][$this->third->id]);
        $this->assertSame($commentId, $joined['members_joined'][$this->third->id]);
        $this->assertFalse($this->chatItem($chat->id)['last_message']['read']);
    }

    public function test_chat_list_read_ticks_ignore_members_added_after_the_message(): void
    {
        $group = $this->makeGroup($this->me, [$this->peer]);
        $mine = $this->say($group, $this->me, 'Завтра в 7:30');
        // Добавлен позже (в ту же секунду — неважно): граница прочитанного — моё сообщение, но он его не читал
        $group->ensureMember($this->third->id, $mine->id);

        $this->signIn($this->me);
        $this->assertFalse($this->chatItem($group->id)['last_message']['read']);

        // Прочитал тот, кто был в группе, — две галочки
        $group->markReadBy($this->peer->id, $mine->id);
        $this->assertTrue($this->chatItem($group->id)['last_message']['read']);

        // Чат объекта — тоже с галочками (раньше всегда одна)
        $contractId = $this->makeContract('112', 'ТП-14 Северная');
        $placement = $this->assignContract($contractId, $this->me, today()->toDateString());
        $object = MessengerChat::forObject($contractId);
        $object->ensureMember($this->me->id);
        $object->ensureMember($this->peer->id);
        $comment = $this->addComment($placement, $this->me, 'Щит смонтирован');
        $this->assertFalse($this->chatItem($object->id)['last_message']['read']);
        // Мастер, которого табель добавил при этом комментарии (граница — сам комментарий), — не прочтение
        $object->ensureMember($this->third->id, $comment->id);
        $this->assertFalse($this->chatItem($object->id)['last_message']['read']);
        $object->markReadBy($this->peer->id, $comment->id);
        $this->assertTrue($this->chatItem($object->id)['last_message']['read']);

        // Личный чат: первое сообщение в ту же секунду, что создан чат, — прочтение видно
        $direct = MessengerChat::getOrCreate($this->me->id, $this->peer->id);
        $hello = $this->say($direct, $this->me, 'Привет');
        $direct->markReadBy($this->peer->id, $hello->id);
        $this->assertTrue($this->chatItem($direct->id)['last_message']['read']);
    }

    // --- B1. Ответ и «Прочитано» из уведомления ---

    public function test_message_push_has_reply_buttons_and_reply_is_not_sent_twice(): void
    {
        $this->pushToken($this->peer, 'ExponentPushToken[peer]');
        $direct = MessengerChat::getOrCreate($this->me->id, $this->peer->id);

        $this->signIn($this->me);
        $first = $this->postJson('/api/messenger/message', ['chat_id' => $direct->id, 'message' => 'Еду', 'client_key' => 'message-77:reply'])
            ->assertSuccessful();
        $id = $first->json('data.id') ?? $first->json('id');
        // Push о сообщении — с кнопками «Ответить» / «Прочитано»
        $this->assertSame('message', $this->pushesTo('ExponentPushToken[peer]')[0]['categoryId'] ?? null);

        // Телефон не дождался ответа (или iOS усыпил приложение) и повторил — второго сообщения нет
        $again = $this->postJson('/api/messenger/message', ['chat_id' => $direct->id, 'message' => 'Еду', 'client_key' => 'message-77:reply'])
            ->assertSuccessful();
        $this->assertSame($id, $again->json('data.id') ?? $again->json('id'));
        $this->assertSame(1, $direct->messages()->where('message', 'Еду')->count());

        // Другой ключ и обычная отправка без ключа — новые сообщения
        $this->postJson('/api/messenger/message', ['chat_id' => $direct->id, 'message' => 'Еду', 'client_key' => 'message-78:reply'])->assertSuccessful();
        $this->postJson('/api/messenger/message', ['chat_id' => $direct->id, 'message' => 'Еду'])->assertSuccessful();
        $this->assertSame(3, $direct->messages()->where('message', 'Еду')->count());

        // Ключ попытки — свой у каждого сотрудника: тот же ключ у собеседника не вернёт моё сообщение
        $this->signIn($this->peer);
        $peers = $this->postJson('/api/messenger/message', ['chat_id' => $direct->id, 'message' => 'Жду', 'client_key' => 'message-77:reply'])->assertSuccessful();
        $this->assertNotSame($id, $peers->json('data.id') ?? $peers->json('id'));

        // Push «вас добавили в группу» — без кнопок (нечего отмечать прочитанным)
        Http::fake(['exp.host/*' => Http::response(['data' => [['status' => 'ok', 'id' => 'ticket']]])]);
        $this->pushToken($this->third, 'ExponentPushToken[third]');
        app(\App\Services\Messenger\MessengerNotifier::class)->addedToGroup($this->makeGroup($this->me, []), $this->me, [$this->third->id]);
        $this->assertArrayNotHasKey('categoryId', $this->pushesTo('ExponentPushToken[third]')[0]);
    }

    public function test_new_android_app_gets_message_push_as_data_only_without_firebase_duplicate(): void
    {
        $direct = MessengerChat::getOrCreate($this->me->id, $this->peer->id);

        // Новое Android-приложение регистрирует токен с data_only, старое — без, iPhone — как всегда
        $this->signIn($this->peer);
        $this->postJson('/api/messenger/calls/push-token', ['token' => 'ExponentPushToken[new-android]', 'platform' => 'android', 'installation_id' => 'a1', 'data_only' => true])->assertOk();
        $this->postJson('/api/messenger/calls/push-token', ['token' => 'ExponentPushToken[old-android]', 'platform' => 'android', 'installation_id' => 'a2'])->assertOk();
        $this->postJson('/api/messenger/calls/push-token', ['token' => 'ExponentPushToken[iphone]', 'platform' => 'ios', 'installation_id' => 'i1', 'data_only' => true])->assertOk();

        $this->signIn($this->me);
        $this->postJson('/api/messenger/message', ['chat_id' => $direct->id, 'message' => 'Щит смонтирован'])->assertSuccessful();

        // Только данные: Firebase не покажет своё уведомление, приложение покажет одно — с кнопками
        $new = $this->pushesTo('ExponentPushToken[new-android]')[0];
        $this->assertArrayNotHasKey('title', $new);
        $this->assertArrayNotHasKey('body', $new);
        $this->assertArrayNotHasKey('sound', $new);
        $this->assertSame('high', $new['priority']);
        $this->assertSame(['Иванов Сергей', 'Щит смонтирован'], [$new['data']['push_title'], $new['data']['push_body']]);
        $this->assertSame('message', $new['data']['type']);

        // Старая версия и iPhone — как раньше: заголовок, текст, звук (iPhone — с кнопками по категории)
        $old = $this->pushesTo('ExponentPushToken[old-android]')[0];
        $iphone = $this->pushesTo('ExponentPushToken[iphone]')[0];
        $this->assertSame(['Иванов Сергей', 'Щит смонтирован'], [$old['title'], $old['body']]);
        $this->assertSame(['Иванов Сергей', 'Щит смонтирован', 'message'], [$iphone['title'], $iphone['body'], $iphone['categoryId']]);
        $this->assertArrayNotHasKey('push_title', $iphone['data']);
    }

    public function test_reply_to_object_chat_from_notification_is_not_duplicated(): void
    {
        $contractId = $this->makeContract('112', 'ТП-14 Северная');
        $this->assignContract($contractId, $this->me, today()->toDateString());
        $chat = MessengerChat::forObject($contractId);
        $chat->ensureMember($this->me->id);

        $this->signIn($this->me, ['mobile-app-can-create-comment:site']);
        $send = fn () => $this->postJson('/api/messenger/message', ['chat_id' => $chat->id, 'message' => 'Принял', 'client_key' => 'message-9:reply'])->assertSuccessful();
        $first = $send()->json('id');
        $this->assertSame($first, $send()->json('id'));
        $this->assertSame(1, \App\Models\TimesheetChat::where('message', 'Принял')->count());
    }

    // --- B3. Превью ссылок ---

    public function test_link_preview_reads_open_graph_in_any_charset_and_keeps_image_on_our_server(): void
    {
        Storage::fake('public');
        $this->fakeDns(['empin-news.ru' => ['93.184.216.34'], 'cdn.empin-news.ru' => ['93.184.216.35']]);
        $html = '<html><head><meta charset="windows-1251"><title>Запасной заголовок</title>'
            . '<meta property="og:site_name" content="Новости энергетики">'
            . '<meta property="og:title" content="Подстанция &laquo;Северная&raquo; введена">'
            . '<meta name="description" content="Пуск   ТП-14 после ремонта">'
            . '<meta property="og:image" content="//cdn.empin-news.ru/cover.png"></head><body></body></html>';
        Http::fake([
            'empin-news.ru/article/12' => Http::response(mb_convert_encoding($html, 'Windows-1251', 'UTF-8'), 200, ['Content-Type' => 'text/html']),
            'cdn.empin-news.ru/cover.png' => Http::response($this->png(600, 315), 200, ['Content-Type' => 'image/png']),
            '*' => Http::response('', 404),
        ]);

        $this->signIn($this->me);
        $preview = $this->getJson('/api/messenger/link-preview?url=' . urlencode('empin-news.ru/article/12'))->assertOk()->json('preview');
        $this->assertSame('https://empin-news.ru/article/12', $preview['url']);
        $this->assertSame('Новости энергетики', $preview['site']);
        $this->assertSame('Подстанция «Северная» введена', $preview['title']);
        $this->assertSame('Пуск ТП-14 после ремонта', $preview['description']);
        $this->assertStringStartsWith('/storage/messenger/previews/', $preview['image']);
        $this->assertSame([600, 315], [$preview['image_width'], $preview['image_height']]);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $preview['image']));

        // Второй раз — из кэша, сайт не запрашивается
        $sent = count(Http::recorded());
        $this->getJson('/api/messenger/link-preview?url=' . urlencode('empin-news.ru/article/12'))->assertOk()->assertJsonPath('preview.title', 'Подстанция «Северная» введена');
        $this->assertSame($sent, count(Http::recorded()));
    }

    public function test_link_preview_never_reaches_internal_network(): void
    {
        $this->fakeDns([
            'intranet.empin-pro.ru' => ['10.0.0.5'],
            'half.empin-pro.ru' => ['93.184.216.34', '127.0.0.1'],
            'jump.empin-pro.ru' => ['93.184.216.34'],
            'site.empin-pro.ru' => ['93.184.216.36'],
        ]);
        Http::fake([
            'jump.empin-pro.ru/*' => Http::response('', 302, ['Location' => 'http://10.0.0.5/secret']),
            'site.empin-pro.ru/file.pdf' => Http::response('%PDF', 200, ['Content-Type' => 'application/pdf']),
            'site.empin-pro.ru/empty' => Http::response('<html><body>нет заголовка</body></html>', 200, ['Content-Type' => 'text/html']),
            '*' => Http::response('<title>внутреннее</title>', 200, ['Content-Type' => 'text/html']),
        ]);

        $this->signIn($this->me);
        foreach ([
            'http://127.0.0.1/admin', 'http://[::1]/', 'http://169.254.169.254/latest/meta-data', 'http://localhost/',
            'http://intranet.empin-pro.ru/', 'http://half.empin-pro.ru/', 'http://100.64.1.1/', 'http://[::ffff:10.0.0.1]/',
            'https://site.empin-pro.ru:8443/', 'ftp://site.empin-pro.ru/', 'http://user:secret@site.empin-pro.ru/',
            'http://jump.empin-pro.ru/go', 'https://site.empin-pro.ru/file.pdf', 'https://site.empin-pro.ru/empty',
        ] as $url) {
            $this->assertNull($this->getJson('/api/messenger/link-preview?url=' . urlencode($url))->assertOk()->json('preview'), $url);
        }

        // Во внутреннюю сеть не ушло ни одного запроса — даже по переадресации
        $this->assertSame([], collect(Http::recorded())->map(fn ($pair) => $pair[0]->url())
            ->filter(fn (string $url) => !str_contains($url, 'jump.empin-pro.ru') && !str_contains($url, 'site.empin-pro.ru'))
            ->values()->all());

        // Без входа — нельзя
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/messenger/link-preview?url=' . urlencode('https://site.empin-pro.ru/empty'))->assertUnauthorized();
    }

    /** @param array<string, string[]> $map хост => адреса; IP в адресе ссылки — как есть */
    private function fakeDns(array $map): void
    {
        $this->app->instance(\App\Services\Messenger\HostResolver::class, new class($map) extends \App\Services\Messenger\HostResolver {
            public function __construct(private array $map)
            {
            }

            public function resolve(string $host): array
            {
                return filter_var($host, FILTER_VALIDATE_IP) ? [$host] : ($this->map[$host] ?? []);
            }
        });
    }

    /** PNG заданного размера без расширения GD */
    private function png(int $width, int $height): string
    {
        $chunk = fn (string $type, string $data) => pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
        $rows = str_repeat("\0" . str_repeat("\0\0\0", $width), $height);

        return "\x89PNG\r\n\x1a\n" . $chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0))
            . $chunk('IDAT', gzcompress($rows)) . $chunk('IEND', '');
    }

    // --- B5. «Избранное» и архив ---

    public function test_saved_messages_is_a_private_chat_with_myself(): void
    {
        $this->pushToken($this->me, 'ExponentPushToken[me]');
        $direct = MessengerChat::getOrCreate($this->me->id, $this->peer->id);
        $note = $this->say($direct, $this->peer, 'Схема щита во вложении');

        $this->signIn($this->me);
        $saved = $this->postJson('/api/messenger/chat/saved')->assertOk()->assertJsonPath('title', 'Избранное')->json('chat_id');
        // Повторное открытие — тот же чат; пустое «Избранное» в списке не показывается
        $this->assertSame($saved, $this->postJson('/api/messenger/chat/saved')->json('chat_id'));
        $this->assertNull($this->chatItem($saved));

        // Заметка себе и пересланное на потом
        $this->postJson('/api/messenger/message', ['chat_id' => $saved, 'message' => 'Купить автомат 25А'])->assertSuccessful();
        $this->postJson('/api/messenger/forward', ['chat_id' => $direct->id, 'message_ids' => [$note->id], 'target_chat_ids' => [$saved]])->assertOk();

        $item = $this->chatItem($saved);
        $this->assertSame('saved', $item['type']);
        $this->assertSame('Избранное', $item['title']);
        $this->assertNull($item['members_count']);
        $this->assertSame(0, $item['unread_count']);
        $this->assertSame('Схема щита во вложении', $item['last_message']['text']);
        $this->assertSame(['Купить автомат 25А', 'Схема щита во вложении'], array_column(
            $this->postJson('/api/messenger/chat/messages', ['chat_id' => $saved, 'limit' => 30])->json('messages'), 'message'));
        $this->getJson("/api/messenger/chat/info?chat_id={$saved}")->assertOk()->assertJsonPath('title', 'Избранное');
        $hit = collect($this->getJson('/api/messenger/search?q=' . urlencode('автомат'))->json('messages'))->firstWhere('chat_id', $saved);
        $this->assertSame('Избранное', $hit['chat_title']);

        // Ни push, ни событий другим: это только моё
        $this->assertSame([], $this->pushesTo('ExponentPushToken[me]'));
        Event::assertNotDispatched(MessengerEvent::class, fn (MessengerEvent $event) => $event->eventName === 'message.created'
            && ($event->payload['chat_id'] ?? null) === $saved);

        // Чужое «Избранное» — как несуществующий чат; у коллеги — своё
        $this->signIn($this->peer);
        $this->postJson('/api/messenger/chat/messages', ['chat_id' => $saved, 'limit' => 30])->assertNotFound();
        $this->assertNotSame($saved, $this->postJson('/api/messenger/chat/saved')->json('chat_id'));
    }

    public function test_archived_chat_leaves_the_list_and_returns_on_new_message_unless_muted(): void
    {
        $direct = MessengerChat::getOrCreate($this->me->id, $this->peer->id);
        $group = $this->makeGroup($this->peer, [$this->me]);
        $this->say($direct, $this->peer, 'Привет');
        $this->say($group, $this->peer, 'Сбор в 7:30');

        $this->signIn($this->me);
        $this->postJson('/api/messenger/chat/pin', ['chat_id' => $direct->id, 'pinned' => true])->assertOk();
        $archived = $this->postJson('/api/messenger/chat/archive', ['chat_id' => $direct->id, 'archived' => true])->assertOk();
        // В архиве не закреплён и не считается в значке
        $this->assertTrue($archived->json('archived'));
        $this->assertFalse($archived->json('pinned'));
        $this->assertSame(1, $archived->json('total_unread'));
        $list = $this->getJson('/api/messenger/chats')->json();
        $this->assertSame(1, $list['total_unread']);
        $this->assertTrue(collect($list['chats'])->firstWhere('chat_id', $direct->id)['archived']);
        $this->assertFalse(collect($list['chats'])->firstWhere('chat_id', $group->id)['archived']);

        // Новое сообщение — чат со звуком возвращается из архива
        $this->signIn($this->peer);
        $this->postJson('/api/messenger/message', ['chat_id' => $direct->id, 'message' => 'Ты где?'])->assertSuccessful();
        $this->signIn($this->me);
        $this->assertFalse($this->chatItem($direct->id)['archived']);
        $this->assertSame(3, $this->getJson('/api/messenger/chats')->json('total_unread'));

        // «Без звука» — остаётся в архиве
        $this->postJson('/api/messenger/chat/mute', ['chat_id' => $group->id, 'muted' => true])->assertOk();
        $this->postJson('/api/messenger/chat/archive', ['chat_id' => $group->id, 'archived' => true])->assertOk();
        $this->signIn($this->peer);
        $this->postJson('/api/messenger/message', ['chat_id' => $group->id, 'message' => 'Отбой'])->assertSuccessful();
        $this->signIn($this->me);
        $this->assertTrue($this->chatItem($group->id)['archived']);

        // Вернуть из архива вручную
        $this->postJson('/api/messenger/chat/archive', ['chat_id' => $group->id, 'archived' => false])->assertOk()->assertJsonPath('archived', false);
        $this->assertFalse($this->chatItem($group->id)['archived']);

        // Чужой чат не архивируется
        $this->signIn($this->third);
        $this->postJson('/api/messenger/chat/archive', ['chat_id' => $direct->id, 'archived' => true])->assertNotFound();
    }

    // --- B2. Форматирование ---

    public function test_chat_list_and_push_show_text_without_formatting_and_hide_spoilers(): void
    {
        $this->pushToken($this->peer, 'ExponentPushToken[peer]');
        $direct = MessengerChat::getOrCreate($this->me->id, $this->peer->id);

        $this->signIn($this->me);
        $this->postJson('/api/messenger/message', [
            'chat_id' => $direct->id,
            'message' => '**Срочно** __проверь__ ~~старый~~ `щит` код ||4512|| и **жирный __курсив__**',
        ])->assertCreated();

        // В ленте текст с маркерами (приложение само его оформит)
        $this->assertStringContainsString('**Срочно**', $direct->messages()->latest('id')->value('message'));

        // В списке чатов и уведомлении — без маркеров, скрытое замаскировано
        $expected = 'Срочно проверь старый щит код ••• и жирный курсив';
        $this->signIn($this->peer);
        $this->assertSame($expected, $this->chatItem($direct->id)['last_message']['text']);
        $this->assertSame($expected, $this->pushesTo('ExponentPushToken[peer]')[0]['body']);
    }
}
