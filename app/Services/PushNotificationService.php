<?php

namespace App\Services;

use App\Models\Staff;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    /**
     * Отправить push-уведомление о входящем звонке
     *
     * @param Staff $callee Принимающий звонок
     * @param Staff $caller Звонящий пользователь
     * @param int $callId ID звонка
     * @return bool
     */
    public function sendIncomingCallNotification(Staff $callee, Staff $caller, int $callId): bool
    {
        try {
            // TODO: Реализовать отправку через FCM после настройки Firebase
            // Требуется:
            // 1. FCM Server Key / Service Account
            // 2. Device tokens пользователей в БД
            // 3. Интеграция kreait/firebase-php или HTTP API

            $payload = [
                'type' => 'incoming_call',
                'call_id' => $callId,
                'caller_id' => $caller->id,
                'caller_name' => $caller->name ?? 'Unknown',
                'caller_avatar' => $caller->avatar ?? null,
            ];

            $title = 'Входящий звонок';
            $body = ($caller->name ?? 'Unknown') . ' звонит вам';

            // Заглушка для логирования
            Log::info('Push notification would be sent', [
                'callee_id' => $callee->id,
                'caller_id' => $caller->id,
                'call_id' => $callId,
                'payload' => $payload,
            ]);

            // Пример отправки через FCM HTTP API (закомментировано):
            /*
            $fcmToken = $callee->fcm_token; // Предполагается поле fcm_token в таблице staff

            if (!$fcmToken) {
                Log::warning('FCM token not found for user', ['user_id' => $callee->id]);
                return false;
            }

            $response = Http::withHeaders([
                'Authorization' => 'key=' . config('services.fcm.server_key'),
                'Content-Type' => 'application/json',
            ])->timeout(5)->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $fcmToken,
                'priority' => 'high',
                'time_to_live' => 30,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                ],
                'data' => $payload,
            ]);

            return $response->successful();
            */

            return true; // Временно возвращаем true (заглушка)
        } catch (\Exception $e) {
            Log::error('Failed to send push notification', [
                'error' => $e->getMessage(),
                'callee_id' => $callee->id,
                'caller_id' => $caller->id,
                'call_id' => $callId,
            ]);

            return false;
        }
    }

    /**
     * Отправить уведомление об отмене звонка
     *
     * @param Staff $callee
     * @param int $callId
     * @return bool
     */
    public function sendCallCancelledNotification(Staff $callee, int $callId): bool
    {
        try {
            $payload = [
                'type' => 'call_cancelled',
                'call_id' => $callId,
            ];

            Log::info('Call cancelled notification would be sent', [
                'callee_id' => $callee->id,
                'call_id' => $callId,
            ]);

            // TODO: Реализовать через FCM

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send call cancelled notification', [
                'error' => $e->getMessage(),
                'callee_id' => $callee->id,
                'call_id' => $callId,
            ]);

            return false;
        }
    }
}
