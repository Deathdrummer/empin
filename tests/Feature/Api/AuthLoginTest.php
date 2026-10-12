<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * POST /api/auth/login — вход мобильного приложения.
 * Тест работает на уже мигрированной базе и откатывает свои записи (DatabaseTransactions).
 */
class AuthLoginTest extends TestCase
{
    use DatabaseTransactions;

    private const PASSWORD = 'correct-horse-42';

    protected function setUp(): void
    {
        parent::setUp();

        $staffId = DB::table('staff')->insertGetId(['fname' => 'Тест', 'sname' => 'Входов']);

        // Так адреса сохраняет админка (crud\UsersNew): кириллический домен — в Punycode
        User::create(['email' => 'login.test@xn--e1afmkfd.xn--p1ai', 'password' => self::PASSWORD, 'staff_id' => $staffId]);
        User::create(['email' => 'login.test@example.com', 'password' => self::PASSWORD, 'staff_id' => $staffId]);
    }

    /**
     * @dataProvider validEmailProvider
     */
    public function test_valid_credentials_return_token(string $email, string $expectedEmail): void
    {
        $this->postJson('/api/auth/login', ['email' => $email, 'password' => self::PASSWORD])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'full_name', 'staff_id', 'permissions']])
            ->assertJsonPath('user.email', $expectedEmail);
    }

    public function validEmailProvider(): array
    {
        return [
            'кириллический домен' => ['login.test@пример.рф', 'login.test@пример.рф'],
            'кириллический домен заглавными' => ['login.test@ПРИМЕР.РФ', 'login.test@пример.рф'],
            'тот же адрес в Punycode' => ['login.test@xn--e1afmkfd.xn--p1ai', 'login.test@пример.рф'],
            'пробелы по краям' => ["  login.test@пример.рф \t", 'login.test@пример.рф'],
            'обычный ASCII-адрес' => ['login.test@example.com', 'login.test@example.com'],
        ];
    }

    /**
     * @dataProvider wrongCredentialsProvider
     */
    public function test_wrong_credentials_return_401(string $email, string $password): void
    {
        $this->postJson('/api/auth/login', ['email' => $email, 'password' => $password])
            ->assertStatus(401)
            ->assertExactJson(['message' => 'Неверный логин или пароль']);
    }

    public function wrongCredentialsProvider(): array
    {
        return [
            'неверный пароль, кириллический домен' => ['login.test@пример.рф', 'wrong-password'],
            'неверный пароль, ASCII' => ['login.test@example.com', 'wrong-password'],
            'пароль с лишним пробелом' => ['login.test@example.com', self::PASSWORD . ' '],
            'неизвестный адрес' => ['nobody@пример.рф', self::PASSWORD],
        ];
    }

    /**
     * @dataProvider malformedRequestProvider
     */
    public function test_missing_or_malformed_fields_return_422(array $payload): void
    {
        $this->postJson('/api/auth/login', $payload)->assertStatus(422);
    }

    public function malformedRequestProvider(): array
    {
        return [
            'пустой запрос' => [[]],
            'пустой email' => [['email' => '', 'password' => self::PASSWORD]],
            'email из пробелов' => [['email' => '   ', 'password' => self::PASSWORD]],
            'email без домена' => [['email' => 'login.test', 'password' => self::PASSWORD]],
            'email массивом' => [['email' => ['login.test@example.com'], 'password' => self::PASSWORD]],
            'нет пароля' => [['email' => 'login.test@example.com']],
            'пароль массивом' => [['email' => 'login.test@example.com', 'password' => [self::PASSWORD]]],
        ];
    }
}
