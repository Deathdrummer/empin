<?php

namespace Tests\Feature\Api;

use App\Models\MessengerChat;
use App\Models\MessengerChatMember;
use App\Models\MessengerMessage;
use App\Models\Staff;
use App\Models\StaffPushToken;
use App\Models\TimesheetChat;
use App\Models\User;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Общие помощники тестов мессенджера: сотрудники, вход, группы, объекты табеля, push.
 */
trait MessengerTestHelpers
{
    /** Самый маленький JPEG 1×1 — для загрузки без расширения GD (PHP 8.1: в трейте нельзя константы) */
    private function tinyJpeg(): string
    {
        return base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');
    }

    private function createStaff(string $fname, string $sname): Staff
    {
        return Staff::find(DB::table('staff')->insertGetId(['fname' => $fname, 'sname' => $sname]));
    }

    /** @param string[] $permissions */
    private function signIn(Staff $staff, array $permissions = []): User
    {
        $user = User::firstOrCreate(['staff_id' => $staff->id], [
            'email' => "messenger{$staff->id}@example.com",
            'password' => 'correct-horse-42',
        ]);
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'site');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->syncPermissions($permissions);
        Sanctum::actingAs($user);

        return $user;
    }

    private function makeGroup(Staff $admin, array $members, string $title = 'Бригада'): MessengerChat
    {
        $chat = MessengerChat::create(['type' => MessengerChat::TYPE_GROUP, 'title' => $title, 'created_by' => $admin->id]);
        $chat->ensureMember($admin->id, null, MessengerChatMember::ROLE_ADMIN);
        foreach ($members as $member) {
            $chat->ensureMember($member->id);
        }

        return $chat;
    }

    private function say(MessengerChat $chat, Staff $from, string $text, array $extra = []): MessengerMessage
    {
        return MessengerMessage::create(['chat_id' => $chat->id, 'from_id' => $from->id, 'message' => $text] + $extra);
    }

    private function makeContract(string $number, string $title): int
    {
        // В схеме из миграций репозитория last_id обязателен, в схеме production — нет
        return DB::table('contracts')->insertGetId([
            'object_number' => $number,
            'title' => $title,
            'created_at' => now(),
            'updated_at' => now(),
        ] + (Schema::hasColumn('contracts', 'last_id') ? ['last_id' => 0] : []));
    }

    private function assignContract(int $contractId, Staff $master, string $day): int
    {
        $teamId = DB::table('timesheet_teams')->insertGetId(['staff_id' => $master->id, 'day' => $day, 'created_at' => now(), 'updated_at' => now()]);

        return DB::table('timesheet_contracts')->insertGetId(['team_id' => $teamId, 'contract_id' => $contractId, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function addComment(int $timesheetContractId, Staff $from, string $text, array $extra = []): TimesheetChat
    {
        return TimesheetChat::create(['timesheet_contract_id' => $timesheetContractId, 'from_id' => $from->id, 'message' => $text] + $extra);
    }

    private function pushToken(Staff $staff, string $token, string $platform = 'ios'): void
    {
        StaffPushToken::create([
            'staff_id' => $staff->id,
            'installation_id' => $token,
            'token' => $token,
            'platform' => $platform,
            'token_type' => 'expo',
        ]);
    }

    /** Чат в списке «Чаты» того, кто вошёл; null — чата в списке нет */
    private function chatItem(int $chatId, string $query = ''): ?array
    {
        return collect($this->getJson('/api/messenger/chats' . $query)->assertOk()->json('chats'))->firstWhere('chat_id', $chatId);
    }

    /** Отправленные push (Expo) на токен */
    private function pushesTo(string $token): array
    {
        return collect(Http::recorded())
            ->map(fn ($pair) => $pair[0])
            ->filter(fn (HttpRequest $request) => str_contains($request->url(), 'exp.host'))
            ->flatMap(fn (HttpRequest $request) => $request->data())
            ->filter(fn ($push) => is_array($push) && ($push['to'] ?? null) === $token)
            ->values()
            ->all();
    }
}
