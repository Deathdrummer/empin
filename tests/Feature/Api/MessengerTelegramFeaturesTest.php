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
