<?php

namespace App\Production\Attendance;

use App\Models\Staff;
use App\Models\StaffNotification;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Уведомления раздела выходов до ручного закрытия и push (п. 7.6, п. 9.3) */
final class AttendanceNotices
{
    public function __construct(private readonly PushNotificationService $push)
    {
    }

    public function send(int $staffId, string $kind, string $title, string $body, array $payload, bool $push = false, ?int $taskId = null): StaffNotification
    {
        $notice = StaffNotification::create([
            'staff_id' => $staffId,
            'kind' => $kind,
            'title' => $title,
            'body' => $body,
            'task_id' => $taskId,
            'payload' => $payload,
        ]);
        if ($push && $staff = Staff::find($staffId)) {
            try {
                $this->push->sendProductionNotice($staff, $title, $body, ['notice_id' => $notice->id, 'kind' => $kind] + ($taskId ? ['task_id' => $taskId] : []));
            } catch (Throwable $error) {
                // Плашка уже создана — без push сотрудник увидит её при открытии приложения
                Log::warning('[Attendance] push not sent: '.$error->getMessage());
            }
        }

        return $notice;
    }

    /** Открытое уведомление этого вида по смене или дню */
    public function open(int $staffId, string $kind, array $match): ?StaffNotification
    {
        $query = StaffNotification::query()->open()->where('staff_id', $staffId)->where('kind', $kind);
        foreach ($match as $key => $value) {
            $query->where("payload->$key", $value);
        }

        return $query->latest('id')->first();
    }

    /** Снять уведомление системой — например, сотрудника включили в заявку (П-СМ-09) */
    public function withdraw(int $staffId, string $kind, array $match): bool
    {
        $notice = $this->open($staffId, $kind, $match);
        if (!$notice) {
            return false;
        }
        $notice->update(['withdrawn_at' => now()]);

        return true;
    }
}
