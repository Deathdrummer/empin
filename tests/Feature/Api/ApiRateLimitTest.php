<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Лимит частоты запросов мобильного API (RateLimiter 'api').
 * Тест работает на уже мигрированной базе и откатывает свои записи (DatabaseTransactions).
 */
class ApiRateLimitTest extends TestCase
{
    use DatabaseTransactions;

    public function test_each_user_has_own_limit_of_300_requests_per_minute(): void
    {
        $first = $this->createUser('limit.first@example.com');
        $second = $this->createUser('limit.second@example.com');

        Sanctum::actingAs($first);
        for ($i = 1; $i <= 300; $i++) {
            $this->getJson('/api/auth/me')->assertOk();
        }
        $this->getJson('/api/auth/me')->assertStatus(429);

        // Раньше счётчик был общий на весь API: второй пользователь тоже получал 429
        Sanctum::actingAs($second);
        $this->getJson('/api/auth/me')->assertOk();
    }

    public function test_login_attempts_are_limited_per_email_and_ip(): void
    {
        $this->createUser('limit.login@example.com');

        for ($i = 1; $i <= 10; $i++) {
            $this->postJson('/api/auth/login', ['email' => 'limit.login@example.com', 'password' => 'wrong'])
                ->assertStatus(401);
        }

        $this->postJson('/api/auth/login', ['email' => ' LIMIT.login@example.com ', 'password' => 'wrong'])
            ->assertStatus(429);

        // Другой адрес с того же IP не блокируется
        $this->postJson('/api/auth/login', ['email' => 'limit.other@example.com', 'password' => 'wrong'])
            ->assertStatus(401);
    }

    private function createUser(string $email): User
    {
        $staffId = DB::table('staff')->insertGetId(['fname' => 'Лимит', 'sname' => 'Тестов']);

        return User::create(['email' => $email, 'password' => 'correct-horse-42', 'staff_id' => $staffId]);
    }
}
