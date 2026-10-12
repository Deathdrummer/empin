<?php

namespace Tests\Feature\Api;

use App\Events\CallEnded;
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
            'services.agora.app_id' => 'test-app-id',
            'services.agora.app_certificate' => 'test-app-certificate',
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

    public function test_login_removes_push_tokens_of_replaced_sessions(): void
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

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => self::PASSWORD])->assertOk();

        $this->assertSame(0, StaffPushToken::where('staff_id', $this->callee->id)->count());
        $this->assertNull($this->callee->fresh()->expo_push_token);
        $this->assertSame(1, StaffPushToken::where('staff_id', $this->caller->id)->count());
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
