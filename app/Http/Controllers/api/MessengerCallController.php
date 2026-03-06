<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\MessengerCall;
use App\Models\Staff;
use App\Services\AgoraService;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MessengerCallController extends Controller
{
    protected PushNotificationService $pushService;
    protected AgoraService $agora;

    public function __construct(PushNotificationService $pushService, AgoraService $agora)
    {
        $this->pushService = $pushService;
        $this->agora = $agora;
    }

    /**
     * Зарегистрировать Expo push token устройства
     */
    public function registerPushToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|max:200',
        ]);

        $staff = Staff::find($request->user()->staff_id);

        if (!$staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }

        $staff->update(['expo_push_token' => $validated['token']]);

        return response()->json(['success' => true]);
    }

    /**
     * Инициировать звонок
     */
    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'callee_id' => 'required|exists:staff,id',
        ]);

        $callerId = $request->user()->staff_id;
        $calleeId = $validated['callee_id'];

        \Log::debug('[CALL initiate]', [
            'user_id'   => $request->user()->id,
            'staff_id'  => $request->user()->staff_id,
            'caller_id' => $callerId,
            'callee_id' => $calleeId,
        ]);

        // Проверка: нельзя позвонить самому себе
        if ($callerId === $calleeId) {
            return response()->json(['message' => 'Cannot call yourself'], 422);
        }

        // Генерация имени канала Agora
        $channelName = AgoraService::generateChannelName();

        // Создание записи звонка
        $call = MessengerCall::create([
            'caller_id' => $callerId,
            'callee_id' => $calleeId,
            'call_type' => 'audio',
            'status' => 'initiated',
            'session_id' => $channelName,
        ]);

        // Загрузка данных callee и caller
        $call->load(['callee', 'caller']);

        // Генерация Agora токена для caller
        $token = $this->agora->generateToken($channelName, $callerId);

        // Отправка push-уведомления callee
        $this->pushService->sendIncomingCallNotification(
            $call->callee,
            $call->caller,
            $call->id
        );

        return response()->json([
            'call_id'      => $call->id,
            'channel_name' => $channelName,
            'token'        => $token,
            'uid'          => $callerId,
            'agora_app_id' => $this->agora->getAppId(),
            'callee'       => $call->callee,
        ]);
    }

    /**
     * Принять звонок
     */
    public function accept(Request $request, int $id): JsonResponse
    {
        $call = MessengerCall::find($id);

        if (!$call) {
            return response()->json(['message' => 'Call not found'], 404);
        }

        if (!in_array($call->status, ['initiated', 'ringing'])) {
            return response()->json(['message' => 'Call cannot be accepted'], 422);
        }

        if ($call->callee_id !== $request->user()->staff_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $call->update([
            'status' => 'active',
            'started_at' => now(),
        ]);

        // Генерация Agora токена для callee
        $token = $this->agora->generateToken($call->session_id, $call->callee_id);

        return response()->json([
            'call'         => $call,
            'channel_name' => $call->session_id,
            'token'        => $token,
            'uid'          => $call->callee_id,
            'agora_app_id' => $this->agora->getAppId(),
        ]);
    }

    /**
     * Отклонить звонок
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $call = MessengerCall::find($id);

        if (!$call) {
            return response()->json(['message' => 'Call not found'], 404);
        }

        if ($call->callee_id !== $request->user()->staff_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $call->update([
            'status' => 'rejected',
            'ended_at' => now(),
        ]);

        // Уведомляем звонящего что звонок отклонён — без этого он ждёт весь таймаут
        $call->load('caller');
        $this->pushService->sendCallCancelledNotification($call->caller, $call->id);

        return response()->json(['success' => true]);
    }

    /**
     * Отменить звонок (до принятия)
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $call = MessengerCall::find($id);

        if (!$call) {
            return response()->json(['message' => 'Call not found'], 404);
        }

        if ($call->status === 'active') {
            return response()->json(['message' => 'Active call cannot be cancelled, use end instead'], 422);
        }

        if ($call->caller_id !== $request->user()->staff_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $call->update([
            'status' => 'cancelled',
            'ended_at' => now(),
        ]);

        // Уведомляем callee, что звонок отменён
        $call->load('callee');
        $this->pushService->sendCallCancelledNotification($call->callee, $call->id);

        return response()->json(['success' => true]);
    }

    /**
     * Завершить звонок
     */
    public function end(Request $request, int $id): JsonResponse
    {
        $call = MessengerCall::find($id);

        if (!$call) {
            return response()->json(['message' => 'Call not found'], 404);
        }

        $userId = $request->user()->staff_id;

        if ($call->caller_id !== $userId && $call->callee_id !== $userId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Определение финального статуса
        $status = 'completed';

        if ($call->status === 'initiated') {
            // Если звонок не был принят
            if ($userId === $call->caller_id) {
                $status = 'cancelled';
            } else {
                $status = 'missed';
            }
        }

        $wasActive = $call->status === 'active';

        $call->update([
            'status' => $status,
            'ended_at' => now(),
        ]);

        // Вычисление длительности для активных звонков
        if ($wasActive) {
            $call->calculateDuration();
        }

        // Agora каналы закрываются автоматически когда все участники уходят.
        // Явное удаление не требуется.

        // Уведомляем другого участника о завершении
        $call->load(['caller', 'callee']);
        $otherParty = ($userId === $call->caller_id) ? $call->callee : $call->caller;
        $this->pushService->sendCallCancelledNotification($otherParty, $call->id);

        return response()->json(['call' => $call]);
    }

    /**
     * Проверка входящих звонков (polling, временно до FCM)
     */
    public function pending(Request $request): JsonResponse
    {
        $userId = $request->user()->staff_id;

        \Log::debug('[CALL pending]', [
            'user_id'  => $request->user()->id,
            'staff_id' => $userId,
        ]);

        $call = MessengerCall::where('callee_id', $userId)
            ->whereIn('status', ['initiated', 'ringing'])
            ->with('caller')
            ->latest()
            ->first();

        \Log::debug('[CALL pending result]', ['found' => (bool)$call, 'call_id' => $call?->id]);

        if (!$call) {
            return response()->json(['call' => null]);
        }

        // Обновляем статус на ringing при первом обнаружении
        if ($call->status === 'initiated') {
            $call->update(['status' => 'ringing']);
        }

        return response()->json([
            'call' => [
                'id' => $call->id,
                'caller' => $call->caller,
                'call_type' => $call->call_type,
                'created_at' => $call->created_at,
            ],
        ]);
    }

    /**
     * История звонков
     */
    public function history(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'nullable|in:all,incoming,outgoing,missed',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $type = $validated['type'] ?? 'all';
        $perPage = $validated['per_page'] ?? 20;
        $userId = $request->user()->staff_id;

        $query = MessengerCall::query()
            ->forUser($userId)
            ->with(['caller', 'callee'])
            ->orderBy('created_at', 'desc');

        // Фильтрация по типу
        switch ($type) {
            case 'incoming':
                $query->incoming($userId);
                break;
            case 'outgoing':
                $query->outgoing($userId);
                break;
            case 'missed':
                $query->missed()->incoming($userId);
                break;
        }

        $calls = $query->paginate($perPage);

        return response()->json([
            'calls' => $calls->items(),
            'pagination' => [
                'current_page' => $calls->currentPage(),
                'total_pages' => $calls->lastPage(),
                'total' => $calls->total(),
                'per_page' => $calls->perPage(),
            ],
        ]);
    }

    /**
     * Получить информацию о звонке
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $call = MessengerCall::with(['caller', 'callee'])->find($id);

        if (!$call) {
            return response()->json(['message' => 'Call not found'], 404);
        }

        $userId = $request->user()->staff_id;

        if ($call->caller_id !== $userId && $call->callee_id !== $userId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(['call' => $call]);
    }
}
