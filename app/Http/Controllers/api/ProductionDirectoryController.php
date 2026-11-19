<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Production\Erp\ErpGateway;
use App\Production\Objects\ObjectDirectory;
use App\Production\Rights\Right;
use App\Production\Rights\StaffRights;
use App\Production\Support\OrgClock;
use App\Production\Support\ProductionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Данные ERP для формы задания (ТЗ, раздел 5): объекты, план объекта, исполнители, техника и водители */
class ProductionDirectoryController extends Controller
{
    private const TASK_FORM_RIGHTS = [Right::TaskCreate, Right::TaskEditOwn, Right::TaskEditOthers];

    public function __construct(
        private readonly OrgClock $clock,
        private readonly ObjectDirectory $objects,
        private readonly ErpGateway $erp,
    ) {
    }

    /**
     * Выбор объекта (п. 5.4): без параметров — подсказки двумя группами; `q` — поиск по активным объектам;
     * `brigade_id` — объекты, за которыми закреплена бригада (можно вместе с `q`).
     */
    public function objects(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => 'nullable|string|max:100', 'brigade_id' => 'nullable|integer']);
        $staffId = $this->requireAny($request, self::TASK_FORM_RIGHTS);
        $today = $this->clock->today();
        $search = isset($data['q']) && trim($data['q']) !== '' ? trim($data['q']) : null;

        $items = match (true) {
            isset($data['brigade_id']) => $this->objects->forBrigade((int) $data['brigade_id'], $staffId, $today, $search),
            $search !== null => $this->objects->search($search, $staffId),
            default => $this->objects->suggestions($staffId, $today),
        };

        return response()->json(['items' => $items]);
    }

    /** План объекта — виды работ для задания (П-ЗД-11); плана может не быть (П-ОБ-31) */
    public function plan(Request $request, int $objectId): JsonResponse
    {
        $this->requireAny($request, self::TASK_FORM_RIGHTS);
        $plan = $this->erp->currentPlan($objectId);
        $types = $plan ? $this->erp->workTypes(array_keys($plan['items'])) : [];

        return response()->json([
            'object_id' => $objectId,
            'revision_id' => $plan['revision_id'] ?? null,
            'items' => $plan ? collect($plan['items'])->map(fn (float $quantity, int $typeId) => [
                'work_type_id' => $typeId,
                'name' => $types[$typeId]['name'] ?? null,
                'unit' => $types[$typeId]['unit'] ?? null,
                'quantity' => $quantity,
            ])->sortBy('name')->values() : [],
        ]);
    }

    /**
     * Общий справочник видов работ независимо от плана объекта (ERP9-005; П-ОБ-29, П-ОБ-33): в отчёте по объекту без
     * плана и для работ вне плана
     */
    public function workTypes(): JsonResponse
    {
        return response()->json([
            'items' => collect($this->erp->workTypes())
                ->map(fn (array $type) => ['id' => $type['id'], 'name' => $type['name'], 'unit' => $type['unit']])->values(),
        ]);
    }

    /**
     * Исполнители (п. 5.4 «Выбор исполнителя»): бригады ERP с бригадиром и текущим составом; сотрудники — все, у кого
     * есть учётная запись в приложении (П-ОБ-21)
     */
    public function executors(Request $request): JsonResponse
    {
        $this->requireAny($request, [...self::TASK_FORM_RIGHTS, Right::TaskInvite]);
        $today = $this->clock->today();

        $working = $this->erp->brigadesWorkingOn($today);
        $members = $this->erp->brigadeMembersOn(array_column($working, 'id'), $today);
        $people = $this->erp->people(array_merge(array_filter(array_column($working, 'foreman_staff_id')), ...array_values($members)));
        $brigades = collect($working)->map(fn (array $brigade) => [
            'id' => $brigade['id'],
            'name' => $brigade['name'],
            'foreman' => $brigade['foreman_staff_id'] ? $this->person($people[$brigade['foreman_staff_id']] ?? null, $brigade['foreman_staff_id']) : null,
            'members' => collect($members[$brigade['id']] ?? [])->map(fn (int $id) => $this->person($people[$id] ?? null, $id))->values(),
        ])->values();

        $staff = collect($this->erp->accountPeople())
            ->map(fn (array $person) => $this->person($person, $person['id']) + ['position' => $person['position']])
            ->values();

        return response()->json(['brigades' => $brigades, 'staff' => $staff]);
    }

    /**
     * Техника и водители (п. 5.4 «Блок «Техника»»): машины и водители с отметкой «не используется» не предлагаются
     * (П-ОБ-23а); у машины — закреплённые водители для подстановки (П-ЗД-16); заменить можно на любого водителя.
     */
    public function vehicles(Request $request): JsonResponse
    {
        $this->requireAny($request, self::TASK_FORM_RIGHTS);

        $active = $this->erp->drivers();
        $people = $this->erp->people(array_column($active, 'staff_id'));
        $drivers = collect($active)
            ->map(fn (array $driver) => ['id' => $driver['id'], 'staff_id' => $driver['staff_id'], 'name' => $people[$driver['staff_id']]['name'] ?? null])
            ->sortBy('name')
            ->values();
        $available = $drivers->pluck('id')->flip();

        $vehicles = collect($this->erp->vehicles())
            ->map(fn (array $vehicle) => [
                'id' => $vehicle['id'],
                'name' => $vehicle['name'],
                'plate' => $vehicle['plate'],
                'driver_ids' => collect($vehicle['driver_ids'])->filter(fn ($id) => $available->has($id))->values(),
            ])->values();

        return response()->json(['vehicles' => $vehicles, 'drivers' => $drivers]);
    }

    /** @param Right[] $rights */
    private function requireAny(Request $request, array $rights): int
    {
        $staffId = (int) $request->user()->staff_id;
        $granted = StaffRights::load($staffId);
        foreach ($rights as $right) {
            if ($granted->has($right)) {
                return $staffId;
            }
        }
        throw ProductionException::forbidden('Нет права ставить или изменять задания');
    }

    private function person(?array $person, int $staffId): array
    {
        return ['id' => $staffId, 'name' => $person['name'] ?? null];
    }
}
