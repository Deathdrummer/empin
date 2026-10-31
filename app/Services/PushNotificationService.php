<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\StaffPushToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PushNotificationService
{
    private const EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    public function __construct(private ApnsVoipService $apnsVoip)
    {
    }

    /**
     * Отправить push-уведомление о входящем звонке
     */
    public function sendIncomingCallNotification(Staff $callee, Staff $caller, int $callId): bool
    {
        $callerName = trim("{$caller->sname} {$caller->fname}");
        $callUuid = Str::uuid()->toString();
        $tokens = $this->tokensFor($callee);
        $attempted = false;
        $delivered = false;
        $deliveredVoipInstallations = [];

        foreach ($tokens->where('token_type', 'voip') as $token) {
            $attempted = true;
            $voipResult = $this->apnsVoip->sendIncomingCall($token->token, [
                'type' => 'incoming_call',
                'call_id' => (string) $callId,
                'call_uuid' => $callUuid,
                'caller_id' => (string) $caller->id,
                'caller_name' => $callerName,
            ]);

            if ($voipResult === ApnsVoipService::INVALID_TOKEN) {
                $this->forgetToken($token->token);
            }

            $voipDelivered = $voipResult === ApnsVoipService::DELIVERED;
            $delivered = $voipDelivered || $delivered;

            if ($voipDelivered && $token->installation_id) {
                $deliveredVoipInstallations[] = $token->installation_id;
            }
        }

        foreach ($tokens->where('token_type', 'expo') as $token) {
            $hasVoipForInstallation = $token->platform === 'ios'
                && $token->installation_id
                && in_array($token->installation_id, $deliveredVoipInstallations, true);

            if ($hasVoipForInstallation) {
                continue;
            }

            $attempted = true;
            $data = [
                'type' => 'incoming_call',
                'call_id' => $callId,
                'caller_id' => $caller->id,
                'caller_name' => $callerName,
            ];

            $payload = [
                'data' => $data,
                'priority' => 'high',
                'channelId' => 'calls',
            ];

            // Если на iPhone ещё нет VoIP-токена, хотя бы показываем обычный push.
            if ($token->platform === 'ios') {
                $payload += [
                    'title' => $callerName,
                    'body' => 'Входящий голосовой звонок',
                    'sound' => 'default',
                    'contentAvailable' => true,
                ];
            }

            $delivered = $this->send($token->token, $payload) || $delivered;
        }

        if (!$attempted) {
            Log::warning('[Push] No registered token for callee', ['callee_id' => $callee->id]);
        }

        return $attempted && $delivered;
    }

    /**
     * Отправить уведомление об отмене/завершении звонка
     */
    public function sendCallCancelledNotification(Staff $callee, int $callId): bool
    {
        return $this->sendCallStateNotification($callee, $callId, 'call_cancelled');
    }

    public function sendCallEndedNotification(Staff $recipient, int $callId): bool
    {
        return $this->sendCallStateNotification($recipient, $callId, 'call_ended');
    }

    private function sendCallStateNotification(Staff $recipient, int $callId, string $type): bool
    {
        return $this->sendToExpoTokens($recipient, [
            'data' => [
                'type' => $type,
                'call_id' => $callId,
            ],
            'priority' => 'high',
            'channelId' => 'calls',
        ], true);
    }

    /**
     * Новое сообщение в мессенджере (личный чат, группа или чат объекта). Показывается системой,
     * когда приложение свёрнуто или закрыто; badge — общее число непрочитанных у получателя.
     */
    public function sendChatMessageNotification(Staff $recipient, string $title, string $body, array $data, int $badge): bool
    {
        $title = $title !== '' ? $title : 'Новое сообщение';

        return $this->sendToExpoTokens($recipient, [
            'title' => $title,
            'body' => $body,
            'sound' => 'default',
            'badge' => $badge,
            'priority' => 'high',
            'data' => ['type' => 'message'] + $data,
        ] + (!empty($data['message_id']) ? [
            // Кнопки «Ответить» и «Прочитано» (категорию регистрирует приложение; старое — покажет без кнопок)
            'categoryId' => 'message',
        ] : []), false, function (array $payload, StaffPushToken $token) use ($title, $body) {
            // Android-приложение само показывает уведомление (с кнопками). С заголовком и текстом push Firebase
            // показал бы ещё и своё — второе, без кнопок. Поэтому только данные: текст — внутри них
            if ($token->platform !== 'android' || !$token->data_only) {
                return $payload;
            }
            unset($payload['title'], $payload['body'], $payload['sound'], $payload['categoryId']);
            $payload['data'] += ['push_title' => $title, 'push_body' => $body];

            return $payload;
        });
    }

    /**
     * Отправить уведомление об активном звонке (звонок принят)
     */
    public function sendCallAcceptedNotification(Staff $caller, int $callId): bool
    {
        return $this->sendToExpoTokens($caller, [
            'data' => [
                'type' => 'call_accepted',
                'call_id' => $callId,
            ],
            'priority' => 'high',
            'channelId' => 'calls',
        ]);
    }

    /**
     * @param ?callable(array, StaffPushToken): array $forToken — свой вариант payload для отдельного устройства
     */
    private function sendToExpoTokens(
        Staff $staff,
        array $payload,
        bool $contentAvailableForIos = false,
        ?callable $forToken = null
    ): bool
    {
        $tokens = $this->tokensFor($staff)->where('token_type', 'expo');
        $attempted = false;
        $delivered = false;

        foreach ($tokens as $token) {
            $attempted = true;
            $tokenPayload = $forToken ? $forToken($payload, $token) : $payload;

            // Старые клиенты без platform и Android получают прежний payload.
            // Только iOS нужен background push для закрытия CallKit; Apple требует для него приоритет 5
            // (Expo: normal), с приоритетом 10 фоновый push считается ошибкой.
            if ($contentAvailableForIos && $token->platform === 'ios') {
                $tokenPayload['contentAvailable'] = true;
                $tokenPayload['priority'] = 'normal';
            }

            $delivered = $this->send($token->token, $tokenPayload) || $delivered;
        }

        return $attempted && $delivered;
    }

    private function tokensFor(Staff $staff)
    {
        $tokens = $staff->pushTokens()->get();

        if ($staff->expo_push_token && !$tokens->contains('token', $staff->expo_push_token)) {
            $tokens->push(new StaffPushToken([
                'token' => $staff->expo_push_token,
                'platform' => 'unknown',
                'token_type' => 'expo',
            ]));
        }

        return $tokens;
    }

    /**
     * Убрать недействительный токен, в том числе из старого поля staff.expo_push_token
     */
    private function forgetToken(string $token): void
    {
        StaffPushToken::where('token', $token)->delete();
        Staff::where('expo_push_token', $token)->update(['expo_push_token' => null]);

        Log::info('[Push] Inactive device token removed');
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
                Log::error('[Push] Expo delivery error', ['result' => $result]);

                // Приложение удалено или push для него отключён — токен больше не сработает
                if (($result['details']['error'] ?? null) === 'DeviceNotRegistered') {
                    $this->forgetToken($token);
                }

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('[Push] Failed to send', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
