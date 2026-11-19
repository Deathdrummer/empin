<?php

namespace App\Production\Erp;

use App\Models\Erp\Brigade;
use App\Models\Erp\PermissionSetting;
use App\Models\Staff;
use App\Models\User;
use App\Production\Objects\ObjectDirectory;
use App\Production\Rights\Right;
use App\Production\Support\ProductionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

/** Настройки прав приложения в ERP (ТЗ, п. 4.2.2; «Доработки ERP», п. 6; интервью 7.2, 8.2, 8.18 — FR-359) */
class RightSettings
{
    public const MAX_DEPTH = 3650;

    public function __construct(private readonly RightsHistory $history)
    {
    }

    /** У права есть настройки (права 2–6, 9, 17–19) */
    public static function hasSettings(Right $right): bool
    {
        return $right->hasDepth() || $right->hasCreatorScope() || $right->hasBrigadeScope()
            || in_array($right, [Right::TaskViewAll, Right::TimesheetView], true);
    }

    /** Право приложения по id права ERP, если это оно */
    public static function rightOf(int $permissionId): ?Right
    {
        $name = Permission::whereKey($permissionId)->where('guard_name', 'site')->value('name');

        return $name ? Right::fromPermissionName($name) : null;
    }

    /** @return Collection<int, PermissionSetting> */
    public function rows(User $user, int $permissionId): Collection
    {
        return PermissionSetting::where('user_id', $user->id)->where('permission_id', $permissionId)->orderBy('id')->get();
    }

    /**
     * Заменить все выдачи права сотрудника новыми (одной транзакцией)
     *
     * @param array<int, array<string, mixed>> $input
     *
     */
    public function save(User $user, Right $right, int $permissionId, array $input): void
    {
        if (!self::hasSettings($right)) {
            throw ProductionException::invalid('У права нет настроек');
        }
        if (!$input) {
            throw ProductionException::invalid('Нужна хотя бы одна настройка');
        }
        $rows = array_map(fn ($row) => $this->normalize($right, (array) $row), array_values($input));
        $this->replace($user, $permissionId, $rows);
    }

    /**
     * Заменить выдачи права сотрудника готовыми строками настроек (из окна ERP или из типового набора); пустой список
     * — настроек нет
     *
     * @param array<int, array<string, mixed>> $rows
     *
     */
    public function replace(User $user, int $permissionId, array $rows): void
    {
        DB::transaction(function () use ($user, $permissionId, $rows) {
            $before = $this->rows($user, $permissionId)->map(fn (PermissionSetting $s) => $s->snapshot())->all();
            if ($before === $rows) {
                return;
            }
            PermissionSetting::where('user_id', $user->id)->where('permission_id', $permissionId)->delete();
            foreach ($rows as $row) {
                PermissionSetting::create(['user_id' => $user->id, 'permission_id' => $permissionId] + $row);
            }
            $this->history->record('settings_changed', $user->id, $permissionId, ['settings' => $before], ['settings' => $rows]);
        });
    }

    /** Строка настроек из окна ERP → поля таблицы */
    public function normalize(Right $right, array $row): array
    {
        $result = ['depth_days' => null, 'scope_all' => false, 'staff_ids' => [], 'brigade_ids' => [], 'all_brigades' => false, 'date_from' => null, 'date_to' => null];
        $scope = (string) ($row['scope'] ?? '');
        $staff = $this->ids($row['staff_ids'] ?? []);
        $brigades = $this->ids($row['brigade_ids'] ?? []);

        if ($right->hasDepth()) {
            if (empty($row['depth_unlimited'])) {
                $days = filter_var($row['depth_days'] ?? null, FILTER_VALIDATE_INT);
                if ($days === false || $days < 1 || $days > self::MAX_DEPTH) {
                    throw ProductionException::invalid('Глубина — число дней не меньше 1 или «без ограничений»');
                }
                $result['depth_days'] = $days;
            }
        }

        if ($right->hasCreatorScope()) {
            $this->scopeAllOrSelected($scope, $staff !== [], 'Выберите создателей заданий');
            $result['scope_all'] = $scope === 'all';
            $result['staff_ids'] = $scope === 'all' ? [] : $this->existingStaff($staff);
        } elseif ($right === Right::TaskViewAll) {
            $this->scopeAllOrSelected($scope, $staff !== [] || $brigades !== [], 'Выберите начальников участков или бригады');
            $result['scope_all'] = $scope === 'all';
            if ($scope !== 'all') {
                $result['staff_ids'] = $this->existingStaff($staff);
                $result['brigade_ids'] = $this->existingBrigades($brigades);
            }
        } elseif ($right->hasBrigadeScope()) {
            $this->scopeAllOrSelected($scope, $brigades !== [], 'Выберите бригады');
            $result['scope_all'] = $scope === 'all';
            $result['brigade_ids'] = $scope === 'all' ? [] : $this->existingBrigades($brigades);
        } elseif ($right === Right::TimesheetView) {
            match ($scope) {
                'company' => $result['scope_all'] = true,
                'all_brigades' => $result['all_brigades'] = true,
                'brigades' => $result['brigade_ids'] = $brigades ? $this->existingBrigades($brigades) : throw ProductionException::invalid('Выберите бригады'),
                'people' => $result['staff_ids'] = $staff ? $this->existingStaff($staff) : throw ProductionException::invalid('Выберите сотрудников'),
                default => throw ProductionException::invalid('Выберите область'),
            };
            $result['date_from'] = $this->date($row['date_from'] ?? null);
            $result['date_to'] = $this->date($row['date_to'] ?? null);
            if ($result['date_from'] && $result['date_to'] && $result['date_from'] > $result['date_to']) {
                throw ProductionException::invalid('Дата «с» позже даты «по»');
            }
        }

        return $result;
    }

    /**
     * Данные вкладки прав приложения в окне прав сотрудника: группа прав ERP и по каждому праву — выдано ли, есть ли
     * настройки и что разрешают выдачи
     *
     * @param int[] $grantedIds все права сотрудника (личные и через роль)
     * @return array{group: ?int, rights: array}
     */
    public function tab(User $user, array $grantedIds): array
    {
        $permissions = Permission::where('guard_name', 'site')->whereIn('name', array_map(fn (Right $r) => $r->permissionName(), Right::cases()))
            ->get(['id', 'name', 'title', 'group']);
        $settings = PermissionSetting::where('user_id', $user->id)->whereIn('permission_id', $permissions->pluck('id'))->orderBy('id')->get()->groupBy('permission_id');

        $rights = $permissions
            ->map(fn (Permission $p) => ['permission' => $p, 'right' => Right::fromPermissionName($p->name)])
            ->sortBy(fn ($item) => $item['right']->value)
            ->map(fn ($item) => [
                'id' => (int) $item['permission']->id,
                'title' => $item['permission']->title ?: $item['right']->title(),
                'has_settings' => self::hasSettings($item['right']),
                'granted' => in_array((int) $item['permission']->id, array_map('intval', $grantedIds), true),
                'summary' => ($settings[$item['permission']->id] ?? collect())->map(fn (PermissionSetting $s) => $this->describe($item['right'], $s->snapshot()))->all(),
            ])->values()->all();

        return ['group' => $permissions->first()?->group !== null ? (int) $permissions->first()->group : null, 'rights' => $rights];
    }

    /**
     * Списки для редактора настроек: сотрудники (для прав 4, 5, 6, 9 — с учётной записью; для права 19 — все работающие)
     * и действующие бригады; уже выбранные показываются, даже если сотрудник уволен или бригада расформирована
     *
     * @return array{0: array, 1: array}
     */
    public function editorOptions(Right $right, array $rows): array
    {
        $selectedStaff = collect($rows)->pluck('staff_ids')->flatten()->filter()->unique()->all();
        $staff = Staff::query()
            ->where(fn ($q) => $right === Right::TimesheetView
                ? $q->where('working', 1)
                : $q->where('working', 1)->whereIn('id', User::whereNotNull('staff_id')->select('staff_id')))
            ->orWhereIn('id', $selectedStaff ?: [0])
            ->orderBy('sname')->orderBy('fname')->get(['id', 'sname', 'fname', 'mname', 'work_post'])
            ->map(fn (Staff $s) => ['id' => (int) $s->id, 'name' => ObjectDirectory::name($s->sname, $s->fname, $s->mname).($s->work_post ? ' — '.$s->work_post : '')])
            ->all();
        $selectedBrigades = collect($rows)->pluck('brigade_ids')->flatten()->filter()->unique()->all();
        $brigades = Brigade::where(fn ($q) => $q->whereNull('disbanded_on')->orWhereIn('id', $selectedBrigades ?: [0]))->orderBy('name')->get(['id', 'name'])
            ->map(fn (Brigade $b) => ['id' => (int) $b->id, 'name' => $b->name])->all();

        return [$staff, $brigades];
    }

    /** Право с настройками нельзя выдать, пока настройки не заданы (интервью 7.2, 8.2) */
    public function canGrant(User $user, int $permissionId): bool
    {
        $right = self::rightOf($permissionId);

        return !$right || !self::hasSettings($right)
            || PermissionSetting::where('user_id', $user->id)->where('permission_id', $permissionId)->exists();
    }

    /** Кратко для окна прав и истории: что разрешает выдача */
    public function describe(Right $right, array $s): string
    {
        $parts = [];
        if ($right->hasDepth()) {
            $parts[] = 'глубина: '.($s['depth_days'] ? $s['depth_days'].' дн.' : 'без ограничений');
        }
        if ($right->hasCreatorScope()) {
            $parts[] = !empty($s['scope_all']) ? 'все задания компании' : 'создатели: '.$this->staffNames($s['staff_ids'] ?? []);
        } elseif ($right === Right::TaskViewAll) {
            if (!empty($s['scope_all'])) {
                $parts[] = 'все задания компании';
            } else {
                if (!empty($s['staff_ids'])) {
                    $parts[] = 'начальники участков: '.$this->staffNames($s['staff_ids']);
                }
                if (!empty($s['brigade_ids'])) {
                    $parts[] = 'бригады: '.$this->brigadeNames($s['brigade_ids']);
                }
            }
        } elseif ($right->hasBrigadeScope()) {
            $parts[] = 'бригады: '.(!empty($s['scope_all']) ? 'без ограничений' : $this->brigadeNames($s['brigade_ids'] ?? []));
        } elseif ($right === Right::TimesheetView) {
            $parts[] = match (true) {
                !empty($s['scope_all']) => 'вся компания',
                !empty($s['all_brigades']) => 'все бригады',
                !empty($s['brigade_ids']) => 'бригады: '.$this->brigadeNames($s['brigade_ids']),
                default => 'сотрудники: '.$this->staffNames($s['staff_ids'] ?? []),
            };
            if (!empty($s['date_from']) || !empty($s['date_to'])) {
                $parts[] = ($s['date_from'] ? 'с '.date('d.m.Y', strtotime($s['date_from'])) : '').($s['date_from'] && $s['date_to'] ? ' ' : '')
                    .($s['date_to'] ? 'по '.date('d.m.Y', strtotime($s['date_to'])) : '');
            }
        }

        return implode('; ', $parts);
    }

    private function scopeAllOrSelected(string $scope, bool $hasSelected, string $emptyMessage): void
    {
        if ($scope === 'all') {
            return;
        }
        if ($scope !== 'selected') {
            throw ProductionException::invalid('Выберите область');
        }
        if (!$hasSelected) {
            throw ProductionException::invalid($emptyMessage);
        }
    }

    /** @return int[] */
    private function ids(mixed $value): array
    {
        $ids = array_map('intval', is_array($value) ? $value : []);

        return array_values(array_unique(array_filter($ids, fn ($id) => $id > 0)));
    }

    /** @param int[] $ids @return int[] */
    private function existingStaff(array $ids): array
    {
        $found = Staff::whereIn('id', $ids)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        if (count($found) !== count($ids)) {
            throw ProductionException::invalid('Сотрудник не найден');
        }

        return $found;
    }

    /** @param int[] $ids @return int[] */
    private function existingBrigades(array $ids): array
    {
        $found = Brigade::whereIn('id', $ids)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        if (count($found) !== count($ids)) {
            throw ProductionException::invalid('Бригада не найдена');
        }

        return $found;
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $time = strtotime((string) $value);
        if ($time === false) {
            throw ProductionException::invalid('Неверная дата');
        }

        return date('Y-m-d', $time);
    }

    /** @param int[] $ids */
    private function staffNames(array $ids): string
    {
        $names = Staff::whereIn('id', $ids)->orderBy('sname')->orderBy('fname')->get(['sname', 'fname', 'mname'])
            ->map(fn (Staff $s) => ObjectDirectory::name($s->sname, $s->fname, $s->mname))->all();

        return $this->list($names);
    }

    /** @param int[] $ids */
    private function brigadeNames(array $ids): string
    {
        return $this->list(Brigade::whereIn('id', $ids)->orderBy('name')->pluck('name')->all());
    }

    private function list(array $names): string
    {
        if (!$names) {
            return '—';
        }
        $shown = array_slice($names, 0, 3);

        return implode(', ', $shown).(count($names) > 3 ? ' и ещё '.(count($names) - 3) : '');
    }
}
