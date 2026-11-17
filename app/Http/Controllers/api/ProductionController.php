<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Erp\BrigadeMember;
use App\Models\Erp\Driver;
use App\Models\Staff;
use App\Models\StaffNotification;
use App\Production\Rights\Grant;
use App\Production\Rights\StaffRights;
use App\Production\Support\OrgClock;
use App\Production\Support\ProductionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Производственный модуль: общие сведения для приложения.
 * Приложение хранит их на устройстве и пользуется ими без связи (ТЗ, п. 9.1, правило 1);
 * окончательная проверка прав — на сервере при каждом действии (п. 9.1, правило 4).
 */
class ProductionController extends Controller
{
    public function __construct(private readonly OrgClock $clock)
    {
    }

    /** Серверное время организации: по нему приложение считает окна 05:00–10:00 и пр. (п. 9.2) */
    public function time(): JsonResponse
    {
        return response()->json($this->timePayload());
    }

    /** Кто я: сотрудник, текущие бригады, водитель ли, права из ERP с настройками */
    public function me(Request $request): JsonResponse
    {
        $staffId = (int) $request->user()->staff_id;
        $staff = Staff::find($staffId);
        if (!$staff) {
            return response()->json(['error' => 'Учётная запись не связана с сотрудником ERP'], 403);
        }

        $today = $this->clock->today();

        // Текущее членство даёт доступ к заданиям бригады (П-ОБ-18); расформированная бригада не действует
        $brigades = BrigadeMember::query()
            ->activeOn($today)
            ->where('staff_id', $staffId)
            ->whereHas('brigade', fn ($q) => $q->where(fn ($b) => $b->whereNull('disbanded_on')->orWhere('disbanded_on', '>', $today->toDateString())))
            ->with('brigade:id,name,foreman_staff_id')
            ->get()
            ->map(fn (BrigadeMember $member) => [
                'id' => $member->brigade->id,
                'name' => $member->brigade->name,
                'is_foreman' => (int) $member->brigade->foreman_staff_id === $staffId,
                'joined_on' => $member->joined_on->toDateString(),
            ])
            ->unique('id')
            ->values();

        $driver = Driver::where('staff_id', $staffId)->first();
        $rights = StaffRights::load($staffId);

        return response()->json([
            'staff' => [
                'id' => $staff->id,
                'full_name' => $staff->full_name,
                'position' => $staff->work_post,
            ],
            'brigades' => $brigades,
            'driver' => $driver ? ['id' => $driver->id, 'not_used' => $driver->not_used] : null,
            'rights' => $rights->all()->map(fn (Grant $grant) => $grant->toArray())->values(),
            'rights_version' => $rights->version(),
            'time' => $this->timePayload(),
        ]);
    }

    /** Уведомления, которые висят до ручного закрытия (п. 9.3) */
    public function notifications(Request $request): JsonResponse
    {
        $items = StaffNotification::query()
            ->open()
            ->where('staff_id', (int) $request->user()->staff_id)
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn (StaffNotification $n) => [
                'id' => $n->id,
                'kind' => $n->kind,
                'title' => $n->title,
                'body' => $n->body,
                'task_id' => $n->task_id,
                'payload' => $n->payload,
                'created_at' => $n->created_at?->toIso8601String(),
            ]);

        return response()->json(['items' => $items]);
    }

    public function closeNotification(Request $request, int $id): JsonResponse
    {
        $closed = StaffNotification::query()
            ->where('id', $id)
            ->where('staff_id', (int) $request->user()->staff_id)
            ->whereNull('closed_at')
            ->update(['closed_at' => now()]);
        if (!$closed && !StaffNotification::where('id', $id)->where('staff_id', (int) $request->user()->staff_id)->exists()) {
            throw ProductionException::notFound('Уведомление не найдено');
        }

        return response()->json(['closed' => true]);
    }

    private function timePayload(): array
    {
        $now = $this->clock->now();

        return [
            'now' => $now->toIso8601String(),
            'now_ms' => (int) $now->getPreciseTimestamp(3),
            'today' => $now->toDateString(),
            'timezone' => $this->clock->timezone(),
        ];
    }
}
