<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\MessengerCall;
use App\Models\Staff;
use App\Services\LiveKitService;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MessengerCallController extends Controller
{
    protected PushNotificationService $pushService;
    protected LiveKitService $liveKit;

    public function __construct(PushNotificationService $pushService, LiveKitService $liveKit)
    {
        $this->pushService = $pushService;
        $this->liveKit = $liveKit;
    }

    /**
     * Инициировать звонок
     */
    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'callee_id' => 'required|exists:staff,id',
        ]);

        $callerId = $request->user()->id;
        $calleeId = $validated['callee_id'];

        // Проверка: нельзя позвонить самому себе
        if ($callerId === $calleeId) {
            return response()->json(['message' => 'Cannot call yourself'], 422);
        }

        // Генерация имени комнаты LiveKit
        $roomName = LiveKitService::generateRoomName();

        // Создание записи звонка
        $call = MessengerCall::create([
            'caller_id' => $callerId,
            'callee_id' => $calleeId,
            'call_type' => 'audio',
            'status' => 'initiated',
            'session_id' => $roomName,
        ]);

        // Загрузка данных callee и caller
        $call->load(['callee', 'caller']);

        // Генерация LiveKit JWT токена для caller
        $callerIdentity = (string) $callerId;
        $token = $this->liveKit->generateToken($roomName, $callerIdentity);

        // Отправка push-уведомления callee
        $this->pushService->sendIncomingCallNotification(
            $call->callee,
            $call->caller,
            $call->id
        );

        return response()->json([
            'call_id'     => $call->id,
            'room_name'   => $roomName,
            'token'       => $token,
            'livekit_url' => $this->liveKit->getUrl(),
            'callee'      => $call->callee,
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

        if ($call->callee_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $call->update([
            'status' => 'active',
            'started_at' => now(),
        ]);

        // Генерация LiveKit JWT токена для callee
        $calleeIdentity = (string) $call->callee_id;
        $token = $this->liveKit->generateToken($call->session_id, $calleeIdentity);

        return response()->json([
            'call'        => $call,
            'room_name'   => $call->session_id,
            'token'       => $token,
            'livekit_url' => $this->liveKit->getUrl(),
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

        if ($call->callee_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $call->update([
            'status' => 'rejected',
            'ended_at' => now(),
        ]);

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

        if ($call->caller_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $call->update([
            'status' => 'cancelled',
            'ended_at' => now(),
        ]);

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

        $userId = $request->user()->id;

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

        $call->update([
            'status' => $status,
            'ended_at' => now(),
        ]);

        // Вычисление длительности для активных звонков
        if ($call->status === 'active') {
            $call->calculateDuration();
        }

        return response()->json(['call' => $call]);
    }

    /**
     * Проверка входящих звонков (polling, временно до FCM)
     */
    public function pending(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $call = MessengerCall::where('callee_id', $userId)
            ->whereIn('status', ['initiated', 'ringing'])
            ->with('caller')
            ->latest()
            ->first();

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
        $userId = $request->user()->id;

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

        $userId = $request->user()->id;

        if ($call->caller_id !== $userId && $call->callee_id !== $userId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(['call' => $call]);
    }
}
