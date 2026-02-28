<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LiveKitService
{
    private string $apiKey;
    private string $apiSecret;
    private string $url;

    public function __construct()
    {
        $this->apiKey = config('services.livekit.api_key');
        $this->apiSecret = config('services.livekit.api_secret');
        $this->url = config('services.livekit.url');
    }

    /**
     * Генерирует JWT токен для участника LiveKit комнаты
     */
    public function generateToken(string $roomName, string $identity, ?int $ttl = 3600): string
    {
        $now = time();

        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT',
        ]));

        $payload = $this->base64UrlEncode(json_encode([
            'exp' => $now + $ttl,
            'iss' => $this->apiKey,
            'sub' => $identity,
            'jti' => $identity,
            'nbf' => $now,
            'iat' => $now,
            'video' => [
                'room'         => $roomName,
                'roomJoin'     => true,
                'canPublish'   => true,
                'canSubscribe' => true,
            ],
        ]));

        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payload}", $this->apiSecret, true)
        );

        return "{$header}.{$payload}.{$signature}";
    }

    /**
     * Возвращает URL LiveKit сервера
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Генерирует уникальное имя комнаты
     */
    public static function generateRoomName(): string
    {
        return 'call-' . Str::uuid()->toString();
    }

    /**
     * Удаляет LiveKit комнату — все участники получают сигнал об отключении мгновенно.
     * Вызывается при завершении звонка чтобы не ждать ICE timeout (~15 сек).
     */
    public function deleteRoom(string $roomName): void
    {
        try {
            $token = $this->generateAdminToken();

            Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Content-Type'  => 'application/json',
            ])->post(
                rtrim($this->url, '/') . '/twirp/livekit.RoomService/DeleteRoom',
                ['room' => $roomName]
            );
        } catch (\Throwable $e) {
            \Log::warning('[LiveKit] deleteRoom failed', ['room' => $roomName, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Генерирует JWT-токен с правами администратора для Room Service API
     */
    private function generateAdminToken(): string
    {
        $now = time();

        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));

        $payload = $this->base64UrlEncode(json_encode([
            'exp'   => $now + 60,
            'iss'   => $this->apiKey,
            'sub'   => 'server',
            'nbf'   => $now,
            'iat'   => $now,
            'video' => ['roomCreate' => true, 'roomList' => true, 'roomAdmin' => true],
        ]));

        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payload}", $this->apiSecret, true)
        );

        return "{$header}.{$payload}.{$signature}";
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
