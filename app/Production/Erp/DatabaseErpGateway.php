<?php

namespace App\Production\Erp;

use App\Models\Erp\Brigade;
use App\Models\Erp\BrigadeMember;
use App\Models\Erp\ContractPlanRevision;
use App\Models\Erp\ContractProductionInfo;
use App\Models\Erp\Driver;
use App\Models\Erp\PermissionSetting;
use App\Models\Erp\Vehicle;
use App\Models\Erp\WorkType;
use App\Models\Staff;
use App\Models\User;
use App\Production\Objects\ObjectDirectory;
use App\Services\AccountAccess;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Шлюз ERP на таблицах этой же базы: ERP и модуль производства — один Laravel (План MVP, п. 1.1). Здесь — все
 * знания о том, как ERP хранит объекты, сотрудников, бригады, технику, виды работ, планы, права и готовность.
 */
final class DatabaseErpGateway implements ErpGateway
{
    public function __construct(private readonly AccountAccess $accounts)
    {
    }

    // ---- Сотрудники ----

    public function people(array $staffIds): array
    {
        if (!$staffIds) {
            return [];
        }
        $result = [];
        foreach (array_chunk(array_values(array_unique(array_map('intval', $staffIds))), 1000) as $chunk) {
            foreach (Staff::query()->whereIn('id', $chunk)->get(['id', 'sname', 'fname', 'mname', 'work_post', 'working']) as $staff) {
                $result[(int) $staff->id] = $this->person($staff);
            }
        }

        return $result;
    }

    public function workingStaffIds(): array
    {
        return Staff::where('working', 1)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function hasAccount(int $staffId): bool
    {
        return $this->accounts->isStaffIdActive($staffId);
    }

    public function accountPeople(): array
    {
        $result = [];
        foreach ($this->accounts->activeStaffQuery()->orderBy('s.sname')->orderBy('s.fname')->get(['s.id', 's.sname', 's.fname', 's.mname', 's.work_post']) as $row) {
            $result[(int) $row->id] = $this->person($row, working: true);
        }

        return $result;
    }

    // ---- Права ----

    public function grants(int $staffId): array
    {
        $user = User::where('staff_id', $staffId)->first();
        if (!$user) {
            return [];
        }
        // Права приложения — в механизме прав ERP (Spatie, guard «site»): личные и через роль
        $permissions = $user->getAllPermissions()
            ->filter(fn ($permission) => $permission->guard_name === 'site')
            ->mapWithKeys(fn ($permission) => [$permission->id => $permission->name]);
        if ($permissions->isEmpty()) {
            return [];
        }
        $settings = PermissionSetting::query()
            ->where('user_id', $user->id)
            ->whereIn('permission_id', $permissions->keys())
            ->orderBy('id')
            ->get()
            ->groupBy('permission_id');

        return $permissions->map(fn (string $name, int $permissionId) => [
            'permission' => $name,
            'settings' => ($settings->get($permissionId) ?? collect())->map(fn (PermissionSetting $row) => [
                'depth_days' => $row->depth_days !== null ? (int) $row->depth_days : null,
                'scope_all' => (bool) $row->scope_all,
                'staff_ids' => array_values(array_map('intval', $row->staff_ids ?? [])),
                'brigade_ids' => array_values(array_map('intval', $row->brigade_ids ?? [])),
                'all_brigades' => (bool) $row->all_brigades,
                'date_from' => $row->date_from?->toDateString(),
                'date_to' => $row->date_to?->toDateString(),
            ])->values()->all(),
        ])->values()->all();
    }

    // ---- Бригады ----

    public function brigades(array $brigadeIds): array
    {
        if (!$brigadeIds) {
            return [];
        }

        return Brigade::query()->whereIn('id', array_values(array_unique($brigadeIds)))->get(['id', 'name', 'foreman_staff_id', 'disbanded_on'])
            ->mapWithKeys(fn (Brigade $brigade) => [(int) $brigade->id => $this->brigade($brigade)])->all();
    }

    public function brigadesWorkingOn(CarbonInterface $date): array
    {
        return Brigade::query()->workingOn($date)->orderBy('name')->orderBy('id')->get(['id', 'name', 'foreman_staff_id', 'disbanded_on'])
            ->map(fn (Brigade $brigade) => $this->brigade($brigade))->all();
    }

    public function brigadeStaffOn(array $brigadeIds, CarbonInterface $date): array
    {
        return Brigade::currentStaff($brigadeIds, $date);
    }

    public function brigadeMembersOn(array $brigadeIds, CarbonInterface $date): array
    {
        if (!$brigadeIds) {
            return [];
        }
        $result = array_fill_keys(array_map('intval', $brigadeIds), []);
        BrigadeMember::activeOn($date)->whereIn('brigade_id', $brigadeIds)->orderBy('joined_on')->orderBy('id')->get(['brigade_id', 'staff_id'])
            ->each(function (BrigadeMember $member) use (&$result) {
                $result[(int) $member->brigade_id][] = (int) $member->staff_id;
            });

        return array_map(fn (array $ids) => array_values(array_unique($ids)), $result);
    }

    public function brigadesOfStaff(int $staffId, CarbonInterface $date): array
    {
        $result = [];
        BrigadeMember::query()
            ->activeOn($date)
            ->where('staff_id', $staffId)
            ->whereHas('brigade', fn ($q) => $q->workingOn($date))
            ->with('brigade:id,name,foreman_staff_id')
            ->orderBy('joined_on')
            ->get()
            ->each(function (BrigadeMember $member) use (&$result, $staffId) {
                $result[(int) $member->brigade_id] ??= [
                    'id' => (int) $member->brigade_id,
                    'name' => $member->brigade->name,
                    'is_foreman' => (int) $member->brigade->foreman_staff_id === $staffId,
                    'joined_on' => $member->joined_on->toDateString(),
                ];
            });
        // Бригадир — в составе своей бригады, даже если ERP не внесла его строкой состава (П-ОБ-16)
        Brigade::where('foreman_staff_id', $staffId)->workingOn($date)->get(['id', 'name'])
            ->each(function (Brigade $brigade) use (&$result) {
                $result[(int) $brigade->id] ??= ['id' => (int) $brigade->id, 'name' => $brigade->name, 'is_foreman' => true, 'joined_on' => null];
            });
        uasort($result, fn ($a, $b) => [$a['name'], $a['id']] <=> [$b['name'], $b['id']]);

        return $result;
    }

    public function memberships(?array $brigadeIds, ?int $staffId = null): array
    {
        if ($brigadeIds === []) {
            return [];
        }

        return BrigadeMember::query()
            ->when($brigadeIds !== null, fn ($q) => $q->whereIn('brigade_id', $brigadeIds))
            ->when($staffId !== null, fn ($q) => $q->where('staff_id', $staffId))
            ->orderBy('joined_on')->orderBy('id')
            ->get(['brigade_id', 'staff_id', 'joined_on', 'left_on'])
            ->map(fn (BrigadeMember $m) => [
                'brigade_id' => (int) $m->brigade_id,
                'staff_id' => (int) $m->staff_id,
                'joined_on' => $m->joined_on->toDateString(),
                'left_on' => $m->left_on?->toDateString(),
            ])->all();
    }

    // ---- Транспорт и водители ----

    public function vehicles(?array $vehicleIds = null): array
    {
        if ($vehicleIds === []) {
            return [];
        }

        return Vehicle::query()
            ->when($vehicleIds === null, fn ($q) => $q->where('not_used', false), fn ($q) => $q->whereIn('id', $vehicleIds))
            ->with('drivers:id')
            ->orderBy('name')->orderBy('id')
            ->get()
            ->mapWithKeys(fn (Vehicle $vehicle) => [(int) $vehicle->id => [
                'id' => (int) $vehicle->id,
                'name' => $vehicle->name,
                'plate' => $vehicle->plate,
                'not_used' => (bool) $vehicle->not_used,
                'driver_ids' => $vehicle->drivers->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            ]])->all();
    }

    public function drivers(?array $driverIds = null): array
    {
        if ($driverIds === []) {
            return [];
        }

        return Driver::query()
            ->when($driverIds === null,
                fn ($q) => $q->where('not_used', false)->whereHas('staff', fn ($s) => $s->where('working', 1)),
                fn ($q) => $q->whereIn('id', $driverIds))
            ->with('staff:id,working')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (Driver $driver) => [(int) $driver->id => $this->driver($driver)])->all();
    }

    public function driverOfStaff(int $staffId): ?array
    {
        $driver = Driver::with('staff:id,working')->where('staff_id', $staffId)->first();

        return $driver ? $this->driver($driver) : null;
    }

    public function driverStaffIds(): array
    {
        return Driver::query()->pluck('staff_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    // ---- Объекты ----

    public function objects(array $objectIds): array
    {
        if (!$objectIds) {
            return [];
        }

        return $this->objectQuery(activeOnly: false)->whereIn('c.id', array_values(array_unique($objectIds)))->get()
            ->mapWithKeys(fn ($row) => [(int) $row->id => $this->object($row)])->all();
    }

    public function searchObjects(string $search, int $limit): array
    {
        return $this->ids($this->applySearch($this->objectQuery(), $search)
            ->orderByRaw('c.object_number = ? DESC', [trim($search)])
            ->orderByRaw('LENGTH(c.object_number), c.object_number')
            ->limit($limit));
    }

    public function objectsOfResponsible(int $staffId, array $exceptIds, bool $incompleteOnly, int $limit): array
    {
        $query = $this->objectQuery()
            ->where('pi.responsible_staff_id', $staffId)
            ->whereNotIn('c.id', $exceptIds ?: [0]);
        if ($incompleteOnly) {
            $this->incomplete($query);
        }

        return $this->ids($query->orderByRaw('LENGTH(c.object_number), c.object_number')->limit($limit));
    }

    public function objectsOfBrigade(int $brigadeId, ?string $search, int $limit): array
    {
        $query = $this->objectQuery()
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('contract_brigades as cb')
                ->whereColumn('cb.contract_id', 'c.id')
                ->where('cb.brigade_id', $brigadeId));
        if ($search !== null) {
            $this->applySearch($query, $search);
        }

        return $this->ids($query->orderByRaw('LENGTH(c.object_number), c.object_number')->limit($limit));
    }

    public function activeObjectIds(array $objectIds, bool $incompleteOnly): array
    {
        if (!$objectIds) {
            return [];
        }
        $found = [];
        foreach (array_chunk(array_values(array_unique($objectIds)), 1000) as $chunk) {
            $query = $this->objectQuery()->whereIn('c.id', $chunk);
            if ($incompleteOnly) {
                $this->incomplete($query);
            }
            foreach ($this->ids($query) as $id) {
                $found[$id] = true;
            }
        }

        return array_values(array_filter(array_map('intval', $objectIds), fn ($id) => isset($found[$id])));
    }

    // ---- Виды работ и план ----

    public function workTypes(?array $workTypeIds = null): array
    {
        if ($workTypeIds === []) {
            return [];
        }
        $query = $workTypeIds === null
            ? WorkType::query()->orderBy('name')->orderBy('id')
            : WorkType::withTrashed()->whereIn('id', array_values(array_unique($workTypeIds)));

        return $query->get()->mapWithKeys(fn (WorkType $type) => [(int) $type->id => [
            'id' => (int) $type->id,
            'name' => $type->name,
            'unit' => $type->unit,
            'deleted' => $type->trashed(),
        ]])->all();
    }

    public function currentPlan(int $objectId): ?array
    {
        $plan = ContractPlanRevision::currentFor($objectId);

        return $plan ? [
            'revision_id' => (int) $plan->id,
            'items' => $plan->items()->orderBy('id')->pluck('quantity', 'work_type_id')->map(fn ($q) => (float) $q)->all(),
        ] : null;
    }

    // ---- Готовность ----

    public function storedReadiness(int $objectId, bool $lock = false): ?float
    {
        $value = ContractProductionInfo::whereKey($objectId)->when($lock, fn ($q) => $q->lockForUpdate())->value('readiness_percent');

        return $value !== null ? (float) $value : null;
    }

    public function storeReadiness(int $objectId, ?float $percent): void
    {
        ContractProductionInfo::updateOrCreate(['contract_id' => $objectId], ['readiness_percent' => $percent, 'readiness_changed_at' => now()]);
    }

    // ---- Внутреннее ----

    private function person(object $staff, ?bool $working = null): array
    {
        return [
            'id' => (int) $staff->id,
            'name' => ObjectDirectory::name($staff->sname, $staff->fname, $staff->mname),
            'patronymic' => $staff->mname,
            'position' => $staff->work_post ?? null,
            'working' => $working ?? (bool) $staff->working,
        ];
    }

    private function brigade(Brigade $brigade): array
    {
        return [
            'id' => (int) $brigade->id,
            'name' => $brigade->name,
            'foreman_staff_id' => $brigade->foreman_staff_id !== null ? (int) $brigade->foreman_staff_id : null,
            'disbanded_on' => $brigade->disbanded_on?->toDateString(),
        ];
    }

    private function driver(Driver $driver): array
    {
        return [
            'id' => (int) $driver->id,
            'staff_id' => (int) $driver->staff_id,
            'not_used' => (bool) $driver->not_used,
            'working' => (bool) $driver->staff?->working,
        ];
    }

    private function object(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'number' => $row->object_number,
            'title' => $row->title,
            'titul' => $row->titul,
            'locality' => $row->locality,
            // Активный — не в архиве; так же ERP отбирает «все активные» (`whereNot('archive', 1)`)
            'active' => (int) $row->archive !== 1,
            'responsible_staff_id' => $row->responsible_staff_id !== null ? (int) $row->responsible_staff_id : null,
            'readiness' => $row->readiness_percent !== null ? (float) $row->readiness_percent : null,
        ];
    }

    private function objectQuery(bool $activeOnly = true): Builder
    {
        $query = DB::table('contracts as c')
            ->leftJoin('contract_production_info as pi', 'pi.contract_id', '=', 'c.id')
            ->select(['c.id', 'c.object_number', 'c.title', 'c.titul', 'c.locality', 'c.archive', 'pi.responsible_staff_id', 'pi.readiness_percent']);

        return $activeOnly ? $query->where(fn ($q) => $q->whereNull('c.archive')->orWhere('c.archive', '<>', 1)) : $query;
    }

    private function applySearch(Builder $query, string $search): Builder
    {
        $like = '%'.addcslashes(trim($search), '\\%_').'%';

        return $query->where(fn ($q) => $q->where('c.object_number', 'like', $like)
            ->orWhere('c.title', 'like', $like)
            ->orWhere('c.titul', 'like', $like)
            ->orWhere('c.locality', 'like', $like));
    }

    private function incomplete(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('pi.readiness_percent')->orWhere('pi.readiness_percent', '<', 100));
    }

    /** @return int[] */
    private function ids(Builder $query): array
    {
        return $query->pluck('c.id')->map(fn ($id) => (int) $id)->all();
    }
}
