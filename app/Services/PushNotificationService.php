<?php

namespace App\Services;

use App\Models\Staff;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    private const EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    /**
     * Отправить push-уведомление о входящем звонке
     */
    public function sendIncomingCallNotification(Staff $callee, Staff $caller, int $callId): bool
    {
        $token = $callee->expo_push_token ?? null;

        if (!$token) {
            Log::warning('[Push] No expo_push_token for callee', ['callee_id' => $callee->id]);
            return false;
        }

        $callerName = trim("{$caller->sname} {$caller->fname}");

        // Data-only: без title/body/sound — иначе Android обработает FCM нативно
        // и JS background task не запустится (displayNotification не вызовется)
        return $this->send($token, [
            'data'      => [
                'type'         => 'incoming_call',
                'call_id'      => $callId,
                'caller_id'    => $caller->id,
                'caller_name'  => $callerName,
            ],
            'priority'  => 'high',
            'channelId' => 'calls',
        ]);
    }

    /**
     * Отправить уведомление об отмене/завершении звонка
     */
    public function sendCallCancelledNotification(Staff $callee, int $callId): bool
    {
        $token = $callee->expo_push_token ?? null;

        if (!$token) {
            return false;
        }

        return $this->send($token, [
            'title'     => 'Звонок завершён',
            'body'      => 'Звонящий отключился',
            'data'      => [
                'type'    => 'call_cancelled',
                'call_id' => $callId,
            ],
            'sound'     => null,
            'priority'  => 'high',
            'channelId' => 'calls',
        ]);
    }

    /**
     * Отправить уведомление об активном звонке (звонок принят)
     */
    public function sendCallAcceptedNotification(Staff $caller, int $callId): bool
    {
        $token = $caller->expo_push_token ?? null;

        if (!$token) {
            return false;
        }

        return $this->send($token, [
            'title'     => null,
            'body'      => null,
            'data'      => [
                'type'    => 'call_accepted',
                'call_id' => $callId,
            ],
            'priority'  => 'high',
            'channelId' => 'calls',
        ]);
    }

    /**
     * Базовый метод отправки через Expo Push API
     */
    private function send(string $token, array $payload): bool
    {
        try {
            $response = Http::withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(5)->post(self::EXPO_PUSH_URL, [$payload + ['to' => $token]]);

            $body = $response->json();

            if (!$response->successful()) {
                Log::error('[Push] Expo API error', ['status' => $response->status(), 'body' => $body]);
                return false;
            }

            // Expo возвращает массив результатов
            $result = $body['data'][0] ?? null;
            if ($result && $result['status'] === 'error') {
                Log::error('[Push] Expo delivery error', ['result' => $result, 'token' => $token]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('[Push] Failed to send', ['error' => $e->getMessage(), 'token' => $token]);
            return false;
        }
    }
}
