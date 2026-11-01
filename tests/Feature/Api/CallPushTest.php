<?php

namespace Tests\Feature\Api;

use App\Events\CallEnded;
use App\Events\CallAccepted;
use App\Events\CallRejected;
use App\Models\MessengerCall;
use App\Models\Staff;
use App\Models\StaffPushToken;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Push-токены и звонки мобильного приложения.
 * Тест работает на уже мигрированной базе и откатывает свои записи (DatabaseTransactions).
 */
class CallPushTest extends TestCase
{
    use DatabaseTransactions;

    private const PASSWORD = 'correct-horse-42';
    private const VOIP_TOKEN = 'aa11bb22cc33dd44ee55ff66aa11bb22cc33dd44ee55ff66aa11bb22cc33dd44';

    private Staff $caller;
    private Staff $callee;
    private ?string $apnsKeyPath = null;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.agora.app_id' => str_repeat('a', 32),
            'services.agora.app_certificate' => str_repeat('b', 32),
        ]);

        $this->caller = $this->createStaff('Звонящий');
        $this->callee = $this->createStaff('Принимающий');
    }

    protected function tearDown(): void
    {
        if ($this->apnsKeyPath) {
            @unlink($this->apnsKeyPath);
        }

        parent::tearDown();
    }

    public function test_login_keeps_other_device_sessions_and_push_tokens(): void
    {
        $user = User::create([
            'email' => 'push.login@example.com',
            'password' => self::PASSWORD,
            'staff_id' => $this->callee->id,
        ]);
        $this->callee->update(['expo_push_token' => 'ExponentPushToken[old-device]']);
        $this->storeToken($this->callee, 'ExponentPushToken[old-device]', 'ios', 'expo', 'old-installation');
        $this->storeToken($this->callee, self::VOIP_TOKEN, 'ios', 'voip', 'old-installation');
        $this->storeToken($this->caller, 'ExponentPushToken[someone-else]', 'android', 'expo', 'other-installation');
        $oldToken = $user->createToken('mobile-app:old-installation');

        $newToken = $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => self::PASSWORD,
            'installation_id' => 'new-android'])->assertOk()->json('token');

        $this->assertSame(2, StaffPushToken::where('staff_id', $this->callee->id)->count());
        $this->assertSame('ExponentPushToken[old-device]', $this->callee->fresh()->expo_push_token);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $oldToken->accessToken->id]);
        $this->assertSame(1, StaffPushToken::where('staff_id', $this->caller->id)->count());
        $this->app['auth']->forgetGuards();
        $this->withToken($oldToken->plainTextToken)->getJson('/api/auth/me')->assertOk()->assertJsonPath('staff_id', $this->callee->id);
        $this->app['auth']->forgetGuards();
        $this->withToken($newToken)->getJson('/api/auth/me')->assertOk()->assertJsonPath('staff_id', $this->callee->id);
    }

    public function test_login_rotates_only_same_installation_and_legacy_login_preserves_devices(): void
    {
        $user = User::create(['email' => 'multi.device@example.com', 'password' => self::PASSWORD, 'staff_id' => $this->callee->id]);
        $phone = $user->createToken('mobile-app:phone');
        $tablet = $user->createToken('mobile-app:tablet');
        $web = $user->createToken('web-integration');
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => self::PASSWORD,
            'installation_id' => 'phone'])->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $phone->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $tablet->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $web->accessToken->id]);
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => self::PASSWORD])->assertOk();
        $this->assertSame(4, $user->tokens()->count());
    }

    public function test_logout_removes_only_current_installation_tokens(): void
    {
        $user = User::create(['email' => 'logout.device@example.com', 'password' => self::PASSWORD, 'staff_id' => $this->callee->id]);
        $this->storeToken($this->callee, self::VOIP_TOKEN, 'ios', 'voip', 'iphone');
        $this->storeToken($this->callee, 'ExponentPushToken[android]', 'android', 'expo', 'android');
        $token = $user->createToken('mobile-app:android');
        $other = $user->createToken('mobile-app:iphone');
        $this->withToken($token->plainTextToken)->postJson('/api/auth/logout')->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $other->accessToken->id]);
        $this->assertSame(1, StaffPushToken::where('staff_id', $this->callee->id)->count());
        $this->assertDatabaseHas('staff_push_tokens', ['staff_id' => $this->callee->id, 'installation_id' => 'iphone']);
    }

    public function test_failed_login_keeps_push_tokens(): void
    {
        User::create([
            'email' => 'push.login@example.com',
            'password' => self::PASSWORD,
            'staff_id' => $this->callee->id,
        ]);
        $this->storeToken($this->callee, self::VOIP_TOKEN, 'ios', 'voip', 'installation');

        $this->postJson('/api/auth/login', ['email' => 'push.login@example.com', 'password' => 'wrong'])->assertStatus(401);

        $this->assertSame(1, StaffPushToken::where('staff_id', $this->callee->id)->count());
    }

    public function test_accept_is_idempotent_and_keeps_original_start_time(): void
    {
        Event::fake([CallAccepted::class]);
        Http::fake();
        $this->actingAsStaff($this->callee);
        $call = $this->createCall('initiated');
        $first = $this->postJson("/api/messenger/calls/{$call->id}/accept")->assertOk();
        $this->travel(5)->seconds();
        $second = $this->postJson("/api/messenger/calls/{$call->id}/accept")->assertOk();
        $this->assertSame($first->json('call.started_at'), $second->json('call.started_at'));
        $this->assertSame($first->json('channel_name'), $second->json('channel_name'));
        $this->assertStringStartsWith('007', $second->json('token'));
        Event::assertDispatchedTimes(CallAccepted::class, 1);
    }

    public function test_answering_finished_call_returns_explicit_message_without_reopening_it(): void
    {
        Event::fake([CallAccepted::class]);
        Http::fake();
        $this->actingAsStaff($this->callee);
        // Завершённый звонок не должен даже обращаться за RTC-токеном.
        config(['services.agora.app_id' => 'invalid']);
        foreach (['cancelled', 'completed', 'missed', 'rejected'] as $status) {
            $call = $this->createCall($status, ['ended_at' => now()->subMinutes(4)]);
            $before = $call->refresh()->getAttributes();
            $this->postJson("/api/messenger/calls/{$call->id}/accept")
                ->assertStatus(422)
                ->assertJsonPath('code', 'call_finished')
                ->assertJsonPath('call_status', $status)
                ->assertJsonPath('message', 'Звонок уже завершён. Попросите собеседника позвонить снова.')
                ->assertJsonMissingPath('token');
            $this->assertSame($before, $call->fresh()->getAttributes());
        }
        Event::assertNotDispatched(CallAccepted::class);
        Http::assertNothingSent();
    }

    public function test_accept_generation_failure_does_not_mark_call_active(): void
    {
        config(['services.agora.app_id' => 'invalid']);
        $this->actingAsStaff($this->callee);
        $call = $this->createCall('initiated');
        $this->postJson("/api/messenger/calls/{$call->id}/accept")->assertStatus(503);
        $this->assertSame('initiated', $call->fresh()->status);
        $this->assertNull($call->fresh()->started_at);
    }

    public function test_expired_unanswered_call_cannot_be_accepted(): void
    {
        $this->actingAsStaff($this->callee);
        $call = $this->createCall('ringing');
        $call->forceFill(['created_at' => now()->subSeconds(91)])->save();
        $this->postJson("/api/messenger/calls/{$call->id}/accept")
            ->assertStatus(422)->assertJsonPath('code', 'call_finished');
        $this->assertNull($call->fresh()->started_at);
    }

    public function test_incoming_expo_push_has_short_delivery_expiry(): void
    {
        $this->storeToken($this->callee, 'ExponentPushToken[android]', 'android', 'expo', 'android');
        Http::fake(['exp.host/*' => Http::response(['data' => [['status' => 'ok']]])]);
        $this->freezeTime();
        app(PushNotificationService::class)->sendIncomingCallNotification($this->callee, $this->caller, 277);
        Http::assertSent(fn (HttpRequest $request) => $request[0]['ttl'] === 90
            && $request[0]['collapseId'] === 'empin-call-277'
            && $request[0]['expiration'] === now()->addSeconds(90)->timestamp
            && $request[0]['data']['expires_at'] === now()->addSeconds(90)->timestamp);
        app(PushNotificationService::class)->sendCallEndedNotification($this->callee, 277);
        Http::assertSent(fn (HttpRequest $request) => $request[0]['data']['type'] === 'call_ended'
            && $request[0]['collapseId'] === 'empin-call-277' && $request[0]['ttl'] === 90);
    }

    public function test_accept_broadcast_failure_does_not_break_rtc_response(): void
    {
        Http::fake();
        $this->actingAsStaff($this->callee);
        $call = $this->createCall('initiated');
        Event::listen(CallAccepted::class, function () { throw new \RuntimeException('broadcast offline'); });
        $this->postJson("/api/messenger/calls/{$call->id}/accept")->assertOk()->assertJsonStructure(['token', 'uid', 'channel_name']);
        $this->assertSame('active', $call->fresh()->status);
    }

    public function test_other_device_cannot_accept_or_timeout_an_active_call(): void
    {
        Event::fake([CallAccepted::class, CallEnded::class, CallRejected::class]);
        Http::fake();
        $user = User::create(['email' => 'answer.device@example.com', 'password' => self::PASSWORD, 'staff_id' => $this->callee->id]);
        $phone = $user->createToken('mobile-app:iphone');
        $tablet = $user->createToken('mobile-app:tablet');
        $call = $this->createCall('initiated');
        $this->withToken($phone->plainTextToken)->postJson("/api/messenger/calls/{$call->id}/accept")->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($tablet->plainTextToken)->postJson("/api/messenger/calls/{$call->id}/accept")->assertStatus(409);
        $this->app['auth']->forgetGuards();
        $this->withToken($tablet->plainTextToken)->postJson("/api/messenger/calls/{$call->id}/end")->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($tablet->plainTextToken)->postJson("/api/messenger/calls/{$call->id}/reject")->assertOk();
        $this->assertSame('active', $call->fresh()->status);
        Event::assertNotDispatched(CallEnded::class);
        Event::assertNotDispatched(CallRejected::class);
        $this->app['auth']->forgetGuards();
        $this->withToken($phone->plainTextToken)->postJson("/api/messenger/calls/{$call->id}/end")->assertOk();
        $this->assertSame('completed', $call->fresh()->status);
    }

    public function test_answer_elsewhere_push_skips_answering_phone_and_silently_closes_other_ios(): void
    {
        Http::fake(['exp.host/*' => Http::response(['data' => [['status' => 'ok']]])]);
        $this->storeToken($this->callee, 'ExponentPushToken[answering]', 'ios', 'expo', 'iphone');
        $this->storeToken($this->callee, 'ExponentPushToken[other]', 'ios', 'expo', 'ipad');
        app(PushNotificationService::class)->sendCallAnsweredElsewhereNotification($this->callee, 123, 'iphone');
        Http::assertSentCount(1);
        Http::assertSent(fn (HttpRequest $request) => $request[0]['to'] === 'ExponentPushToken[other]'
            && $request[0]['data']['type'] === 'call_answered_elsewhere'
            && $request[0]['contentAvailable'] === true && $request[0]['priority'] === 'normal');
    }

    public function test_registering_device_releases_legacy_token_of_previous_owner(): void
    {
        $this->caller->update(['expo_push_token' => 'ExponentPushToken[shared-phone]']);
        $this->actingAsStaff($this->callee);

        $this->postJson('/api/messenger/calls/push-token', [
            'token' => 'ExponentPushToken[shared-phone]',
            'platform' => 'android',
            'installation_id' => 'phone-installation',
        ])->assertOk();

        $this->assertNull($this->caller->fresh()->expo_push_token);
        $this->assertSame('ExponentPushToken[shared-phone]', $this->callee->fresh()->expo_push_token);
    }

    public function test_unregistered_voip_token_is_removed_and_expo_fallback_is_sent(): void
    {
        $this->enableApns();
        $this->storeToken($this->callee, self::VOIP_TOKEN, 'ios', 'voip', 'iphone');
        $this->storeToken($this->callee, 'ExponentPushToken[iphone]', 'ios', 'expo', 'iphone');
        Http::fake([
            'api.push.apple.com/*' => Http::response(['reason' => 'Unregistered'], 410),
            'exp.host/*' => Http::response(['data' => [['status' => 'ok', 'id' => 'ticket']]]),
        ]);

        app(PushNotificationService::class)->sendIncomingCallNotification($this->callee, $this->caller, 1);

        $this->assertFalse(StaffPushToken::where('token', self::VOIP_TOKEN)->exists());
        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), 'exp.host')
            && $request[0]['to'] === 'ExponentPushToken[iphone]'
            && $request[0]['body'] === 'Входящий голосовой звонок');
    }

    public function test_temporary_apns_failure_keeps_voip_token(): void
    {
        $this->enableApns();
        $this->storeToken($this->callee, self::VOIP_TOKEN, 'ios', 'voip', 'iphone');
        Http::fake(['api.push.apple.com/*' => Http::response(['reason' => 'InternalServerError'], 500)]);

        app(PushNotificationService::class)->sendIncomingCallNotification($this->callee, $this->caller, 1);

        $this->assertTrue(StaffPushToken::where('token', self::VOIP_TOKEN)->exists());
    }

    public function test_delivered_voip_push_suppresses_duplicate_expo_push_for_same_iphone(): void
    {
        $this->enableApns();
        $this->storeToken($this->callee, self::VOIP_TOKEN, 'ios', 'voip', 'iphone');
        $this->storeToken($this->callee, 'ExponentPushToken[iphone]', 'ios', 'expo', 'iphone');
        Http::fake([
            'api.push.apple.com/*' => Http::response(null, 200),
            'exp.host/*' => Http::response(['data' => [['status' => 'ok', 'id' => 'ticket']]]),
        ]);

        app(PushNotificationService::class)->sendIncomingCallNotification($this->callee, $this->caller, 7);

        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), '/3/device/' . self::VOIP_TOKEN)
            && $request->header('apns-push-type') === ['voip']
            && $request->header('apns-topic') === ['ru.deathdrumer.empinmobile.voip']
            && $request['call_id'] === '7');
        Http::assertNotSent(fn (HttpRequest $request) => str_contains($request->url(), 'exp.host'));
    }

    public function test_device_not_registered_expo_token_is_removed(): void
    {
        $this->callee->update(['expo_push_token' => 'ExponentPushToken[deleted-app]']);
        $this->storeToken($this->callee, 'ExponentPushToken[deleted-app]', 'android', 'expo', 'android');
        Http::fake(['exp.host/*' => Http::response(['data' => [[
            'status' => 'error',
            'message' => 'not a registered push notification recipient',
            'details' => ['error' => 'DeviceNotRegistered'],
        ]]])]);

        app(PushNotificationService::class)->sendCallEndedNotification($this->callee, 1);

        $this->assertFalse(StaffPushToken::where('token', 'ExponentPushToken[deleted-app]')->exists());
        $this->assertNull($this->callee->fresh()->expo_push_token);
    }

    public function test_ios_silent_push_uses_normal_priority_and_android_keeps_high(): void
    {
        $this->storeToken($this->callee, 'ExponentPushToken[iphone]', 'ios', 'expo', 'iphone');
        $this->storeToken($this->callee, 'ExponentPushToken[android]', 'android', 'expo', 'android');
        Http::fake(['exp.host/*' => Http::response(['data' => [['status' => 'ok', 'id' => 'ticket']]])]);

        app(PushNotificationService::class)->sendCallCancelledNotification($this->callee, 5);

        Http::assertSent(fn (HttpRequest $request) => $request[0]['to'] === 'ExponentPushToken[iphone]'
            && $request[0]['priority'] === 'normal'
            && $request[0]['contentAvailable'] === true
            && !isset($request[0]['title']));
        Http::assertSent(fn (HttpRequest $request) => $request[0]['to'] === 'ExponentPushToken[android]'
            && $request[0]['priority'] === 'high'
            && !isset($request[0]['contentAvailable']));
    }

    public function test_repeated_end_does_not_change_finished_call(): void
    {
        Http::fake();
        Event::fake([CallEnded::class]);
        $endedAt = now()->subMinute()->startOfSecond();
        $call = $this->createCall('completed', ['started_at' => now()->subMinutes(3), 'ended_at' => $endedAt, 'duration' => 120]);
        $this->actingAsStaff($this->callee);

        $this->postJson("/api/messenger/calls/{$call->id}/end")->assertOk();

        $call->refresh();
        $this->assertSame('completed', $call->status);
        $this->assertTrue($call->ended_at->equalTo($endedAt));
        $this->assertSame(120, $call->duration);
        Event::assertNotDispatched(CallEnded::class);
        Http::assertNothingSent();
    }

    public function test_callee_end_of_ringing_call_marks_it_missed(): void
    {
        Http::fake();
        Event::fake([CallEnded::class]);
        $call = $this->createCall('ringing');
        $this->actingAsStaff($this->callee);

        $this->postJson("/api/messenger/calls/{$call->id}/end")->assertOk();

        $this->assertSame('missed', $call->fresh()->status);
        Event::assertDispatched(CallEnded::class);
    }

    public function test_reject_after_caller_cancelled_keeps_cancelled_status(): void
    {
        Http::fake();
        Event::fake([CallRejected::class]);
        $call = $this->createCall('cancelled', ['ended_at' => now()]);
        $this->actingAsStaff($this->callee);

        $this->postJson("/api/messenger/calls/{$call->id}/reject")->assertOk();

        $this->assertSame('cancelled', $call->fresh()->status);
        Event::assertNotDispatched(CallRejected::class);
        Http::assertNothingSent();
    }

    public function test_repeated_initiate_returns_same_ringing_call_without_second_push(): void
    {
        $this->storeToken($this->callee, 'ExponentPushToken[android]', 'android', 'expo', 'android');
        Http::fake(['exp.host/*' => Http::response(['data' => [['status' => 'ok', 'id' => 'ticket']]])]);
        $this->actingAsStaff($this->caller);

        $first = $this->postJson('/api/messenger/calls/initiate', ['callee_id' => $this->callee->id])->assertOk();
        $second = $this->postJson('/api/messenger/calls/initiate', ['callee_id' => $this->callee->id])->assertOk();

        $this->assertSame($first->json('call_id'), $second->json('call_id'));
        $this->assertSame($first->json('channel_name'), $second->json('channel_name'));
        $this->assertSame(1, MessengerCall::where('caller_id', $this->caller->id)->count());
        Http::assertSentCount(1);
    }

    public function test_initiate_creates_new_call_after_previous_one_finished_or_expired(): void
    {
        Http::fake();
        $this->actingAsStaff($this->caller);
        $cancelled = $this->createCall('cancelled', ['ended_at' => now()]);
        $stale = $this->createCall('initiated');
        $stale->forceFill(['created_at' => now()->subSeconds(31)])->save();

        $callId = $this->postJson('/api/messenger/calls/initiate', ['callee_id' => $this->callee->id])
            ->assertOk()
            ->json('call_id');

        $this->assertNotContains($callId, [$cancelled->id, $stale->id]);
    }

    private function createStaff(string $name): Staff
    {
        return Staff::find(DB::table('staff')->insertGetId(['fname' => $name, 'sname' => 'Тестов']));
    }

    private function storeToken(Staff $staff, string $token, string $platform, string $type, string $installationId): void
    {
        StaffPushToken::create([
            'staff_id' => $staff->id,
            'installation_id' => $installationId,
            'token' => $token,
            'platform' => $platform,
            'token_type' => $type,
        ]);
    }

    private function actingAsStaff(Staff $staff): void
    {
        Sanctum::actingAs(User::create([
            'email' => "staff{$staff->id}@example.com",
            'password' => self::PASSWORD,
            'staff_id' => $staff->id,
        ]));
    }

    private function createCall(string $status, array $attributes = []): MessengerCall
    {
        return MessengerCall::create($attributes + [
            'caller_id' => $this->caller->id,
            'callee_id' => $this->callee->id,
            'call_type' => 'audio',
            'status' => $status,
            'session_id' => 'test-channel',
        ]);
    }

    private function enableApns(): void
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        openssl_pkey_export($key, $pem);
        $this->apnsKeyPath = tempnam(sys_get_temp_dir(), 'apns');
        file_put_contents($this->apnsKeyPath, $pem);

        config([
            'services.apns.enabled' => true,
            'services.apns.team_id' => 'TEAMID1234',
            'services.apns.key_id' => 'KEYID12345',
            'services.apns.private_key_path' => $this->apnsKeyPath,
            'services.apns.bundle_id' => 'ru.deathdrumer.empinmobile',
            'services.apns.environment' => 'production',
        ]);
    }
}
