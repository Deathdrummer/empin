<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Erp\Brigade;
use App\Models\Erp\BrigadeMember;
use App\Models\Erp\ContractPlanRevision;
use App\Models\Erp\Driver;
use App\Models\Erp\Vehicle;
use App\Production\Objects\ObjectDirectory;
use App\Production\Rights\Right;
use App\Production\Rights\StaffRights;
use App\Production\Support\OrgClock;
use App\Production\Support\ProductionException;
use App\Services\AccountAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Данные ERP для формы задания (ТЗ, раздел 5): объекты, план объекта, исполнители, техника и водители.
 * В приложении они только читаются (п. 5.1). Нужны тому, кто ставит или меняет задания (права 1, 2, 4 — п. 5.2);
 * список пользователей — ещё и для приглашения (право 7).
 */
class ProductionDirectoryController extends Controller
{
    private const TASK_FORM_RIGHTS = [Right::TaskCreate, Right::TaskEditOwn, Right::TaskEditOthers];

    public function __construct(
        private readonly OrgClock $clock,
        private readonly ObjectDirectory $objects,
        private readonly AccountAccess $accounts,
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
        $plan = ContractPlanRevision::currentFor($objectId);

        return response()->json([
            'object_id' => $objectId,
            'revision_id' => $plan?->id,
            'items' => $plan ? $plan->items()->with(['workType' => fn ($q) => $q->withTrashed()])->get()->map(fn ($item) => [
                'work_type_id' => (int) $item->work_type_id,
                'name' => $item->workType?->name,
                'unit' => $item->workType?->unit,
                'quantity' => (float) $item->quantity,
            ])->sortBy('name')->values() : [],
        ]);
    }

    /**
     * Исполнители (п. 5.4 «Выбор исполнителя»): бригады ERP с бригадиром и текущим составом; сотрудники — все, у кого
     * есть учётная запись в приложении (П-ОБ-21). Тот же список сотрудников — для приглашения в задание (право 7).
     */
    public function executors(Request $request): JsonResponse
    {
        $this->requireAny($request, [...self::TASK_FORM_RIGHTS, Right::TaskInvite]);
        $today = $this->clock->today();

        $members = BrigadeMember::activeOn($today)->with('staff:id,sname,fname,mname')->orderBy('joined_on')->get()->groupBy('brigade_id');
        $brigades = Brigade::query()
            ->where(fn ($q) => $q->whereNull('disbanded_on')->orWhere('disbanded_on', '>', $today->toDateString()))
            ->with('foreman:id,sname,fname,mname')
            ->orderBy('name')
            ->get()
            ->map(fn (Brigade $brigade) => [
                'id' => $brigade->id,
                'name' => $brigade->name,
                'foreman' => $brigade->foreman ? $this->person($brigade->foreman) : null,
                'members' => ($members->get($brigade->id) ?? collect())->map(fn ($m) => $this->person($m->staff))->values(),
            ]);

        $staff = $this->accounts->activeStaffQuery()
            ->orderBy('s.sname')->orderBy('s.fname')
            ->get(['s.id', 's.sname', 's.fname', 's.mname', 's.work_post'])
            ->map(fn ($row) => $this->person($row) + ['position' => $row->work_post]);

        return response()->json(['brigades' => $brigades, 'staff' => $staff]);
    }

    /**
     * Техника и водители (п. 5.4 «Блок «Техника»»): машины и водители с отметкой «не используется» не предлагаются
     * (П-ОБ-23а); у машины — закреплённые водители для подстановки (П-ЗД-16); заменить можно на любого водителя.
     */
    public function vehicles(Request $request): JsonResponse
    {
        $this->requireAny($request, self::TASK_FORM_RIGHTS);

        $drivers = Driver::query()
            ->where('not_used', false)
            ->whereHas('staff', fn ($q) => $q->where('working', 1))
            ->with('staff:id,sname,fname,mname')
            ->get()
            ->map(fn (Driver $driver) => ['id' => $driver->id, 'staff_id' => $driver->staff_id, 'name' => $this->person($driver->staff)['name']])
            ->sortBy('name')
            ->values();
        $available = $drivers->pluck('id')->flip();

        $vehicles = Vehicle::query()
            ->where('not_used', false)
            ->with('drivers:id')
            ->orderBy('name')
            ->get()
            ->map(fn (Vehicle $vehicle) => [
                'id' => $vehicle->id,
                'name' => $vehicle->name,
                'plate' => $vehicle->plate,
                'driver_ids' => $vehicle->drivers->pluck('id')->filter(fn ($id) => $available->has($id))->values(),
            ]);

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

    private function person(?object $staff): array
    {
        return ['id' => $staff ? (int) $staff->id : null, 'name' => $staff ? ObjectDirectory::name($staff->sname, $staff->fname, $staff->mname) : null];
    }
}
