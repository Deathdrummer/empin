<?php

namespace App\Production\Erp;

use App\Models\Erp\PermissionSetting;
use App\Models\Erp\RolePermissionSetting;
use App\Models\Staff;
use App\Models\User;
use App\Production\Rights\Right;
use App\Production\Support\ProductionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Типовые наборы прав приложения по должностям (ТЗ, п. 4.2.3; П-ПР-26; «Доработки ERP», п. 6) */
class RightSets
{
    public const PREFIX = 'app-set-';

    public function __construct(private readonly RightSettings $settings, private readonly RightsHistory $history)
    {
    }

    /** @return Collection<int, Role> */
    public function all(): Collection
    {
        return Role::where('guard_name', 'site')->where('name', 'like', self::PREFIX.'%')->orderBy('id')->get();
    }

    public function find(int $id): Role
    {
        return Role::whereKey($id)->where('guard_name', 'site')->where('name', 'like', self::PREFIX.'%')->first()
            ?? throw ProductionException::notFound('Набор не найден');
    }

    public function create(string $title): Role
    {
        $title = $this->title($title);

        return Role::create(['name' => self::PREFIX.Str::lower(Str::random(10)), 'title' => $title, 'guard_name' => 'site']);
    }

    public function rename(Role $set, string $title): void
    {
        $set->update(['title' => $this->title($title, $set->id)]);
    }

    /** Права приложения: id права ERP → право */
    public function appPermissions(): Collection
    {
        return Permission::where('guard_name', 'site')->whereIn('name', array_map(fn (Right $r) => $r->permissionName(), Right::cases()))
            ->get(['id', 'name', 'title'])
            ->mapWithKeys(fn (Permission $p) => [(int) $p->id => Right::fromPermissionName($p->name)]);
    }

    /** Строки набора для окна: право, есть ли в наборе, его настройки */
    public function rows(Role $set): array
    {
        $titles = Permission::whereIn('id', $this->appPermissions()->keys())->pluck('title', 'id');
        $inSet = $set->permissions()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $settings = RolePermissionSetting::where('role_id', $set->id)->orderBy('id')->get()->groupBy('permission_id');

        return $this->appPermissions()->sortBy(fn (Right $r) => $r->value)->map(fn (Right $right, int $id) => [
            'id' => $id,
            'title' => $titles[$id] ?: $right->title(),
            'has_settings' => RightSettings::hasSettings($right),
            'granted' => in_array($id, $inSet, true),
            'summary' => $this->summary($right, $settings[$id] ?? collect()),
        ])->values()->all();
    }

    /** Кратко о наборе для списка: какие права */
    public function brief(Role $set): string
    {
        $ids = $set->permissions()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $titles = Permission::whereIn('id', $ids)->pluck('title', 'id');
        $rights = $this->appPermissions()->only($ids)->sortBy(fn (Right $r) => $r->value)
            ->map(fn (Right $r, int $id) => $titles[$id] ?: $r->title());

        return $rights->isEmpty() ? '-' : $rights->implode(', ');
    }

    public function setRight(Role $set, int $permissionId, bool $granted): void
    {
        if (!$this->appPermissions()->has($permissionId)) {
            throw ProductionException::invalid('Это не право приложения');
        }
        if ($granted && !$set->hasPermissionTo($permissionId)) {
            $set->givePermissionTo($permissionId);
            $this->history->record('role_permission_granted', null, $permissionId, null, null, (int) $set->id);
        } elseif (!$granted && $set->hasPermissionTo($permissionId)) {
            $set->revokePermissionTo($permissionId);
            RolePermissionSetting::where('role_id', $set->id)->where('permission_id', $permissionId)->delete();
            $this->history->record('role_permission_revoked', null, $permissionId, null, null, (int) $set->id);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** Настройки права в наборе; пустой список — настройки задаются при применении */
    public function saveSettings(Role $set, int $permissionId, array $input): array
    {
        $right = $this->appPermissions()->get($permissionId) ?? throw ProductionException::invalid('Это не право приложения');
        if (!RightSettings::hasSettings($right)) {
            throw ProductionException::invalid('У права нет настроек');
        }
        $rows = array_map(fn ($row) => $this->settings->normalize($right, (array) $row), array_values($input));
        DB::transaction(function () use ($set, $permissionId, $rows) {
            RolePermissionSetting::where('role_id', $set->id)->where('permission_id', $permissionId)->delete();
            foreach ($rows as $row) {
                RolePermissionSetting::create(['role_id' => $set->id, 'permission_id' => $permissionId] + $row);
            }
        });

        return $this->summary($right, RolePermissionSetting::where('role_id', $set->id)->where('permission_id', $permissionId)->orderBy('id')->get());
    }

    /** @return array<int, array<string, mixed>> */
    public function settingsRows(Role $set, int $permissionId): array
    {
        return RolePermissionSetting::where('role_id', $set->id)->where('permission_id', $permissionId)->orderBy('id')->get()
            ->map(fn (RolePermissionSetting $s) => $s->snapshot())->all();
    }

    /**
     * Выбор набора для сотрудника: все наборы и подсказка по должности — набор, в названии которого есть должность
     * (части названия через «/» или «,»)
     *
     * @return array{0: array, 1: ?int}
     */
    public function choices(int $staffId): array
    {
        $post = Str::lower(trim((string) Staff::whereKey($staffId)->value('work_post')));
        $suggested = null;
        $options = $this->all()->map(function (Role $set) use ($post, &$suggested) {
            $parts = array_map(fn ($p) => Str::lower(trim($p)), preg_split('/[\/,]/u', (string) $set->title));
            if ($post !== '' && $suggested === null && in_array($post, $parts, true)) {
                $suggested = (int) $set->id;
            }

            return ['value' => $set->id, 'title' => $set->title];
        })->all();

        return [$options, $suggested];
    }

    /**
     * Применить набор: права приложения сотрудника становятся правами набора (личными) с копией настроек набора
     *
     * @return int[] права, для которых нужны настройки
     *
     */
    public function apply(User $user, Role $set): array
    {
        $app = $this->appPermissions();
        $inSet = $set->permissions()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $defaults = RolePermissionSetting::where('role_id', $set->id)->orderBy('id')->get()->groupBy('permission_id');
        $direct = $user->permissions()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $needSettings = [];

        DB::transaction(function () use ($user, $set, $app, $inSet, $defaults, $direct, &$needSettings) {
            foreach ($app as $permissionId => $right) {
                if (in_array($permissionId, $inSet, true)) {
                    if (RightSettings::hasSettings($right)) {
                        $rows = ($defaults[$permissionId] ?? collect())->map(fn (RolePermissionSetting $s) => $s->snapshot())->all();
                        if ($rows) {
                            $this->settings->replace($user, $permissionId, $rows);
                        } elseif (!PermissionSetting::where('user_id', $user->id)->where('permission_id', $permissionId)->exists()) {
                            $needSettings[] = $permissionId;
                            continue;
                        }
                    }
                    if (!in_array($permissionId, $direct, true)) {
                        $user->givePermissionTo($permissionId);
                        $this->history->record('granted', $user->id, $permissionId);
                    }
                } elseif (in_array($permissionId, $direct, true)) {
                    $user->revokePermissionTo($permissionId);
                    $this->settings->replace($user, $permissionId, []);
                    $this->history->record('revoked', $user->id, $permissionId);
                }
            }
            $this->history->record('set_applied', $user->id, null, null, ['set' => $set->title, 'need_settings' => $needSettings], (int) $set->id);
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $needSettings;
    }

    private function summary(Right $right, Collection $rows): array
    {
        return $rows->map(fn (RolePermissionSetting $s) => $this->settings->describe($right, $s->snapshot()))->all();
    }

    private function title(string $title, ?int $exceptId = null): string
    {
        $title = mb_substr(trim(preg_replace('/\s+/u', ' ', $title)), 0, 255);
        if ($title === '') {
            throw ProductionException::invalid('Укажите название набора');
        }
        $taken = Role::where('guard_name', 'site')->where('name', 'like', self::PREFIX.'%')->where('title', $title)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))->exists();
        if ($taken) {
            throw ProductionException::invalid("Набор «{$title}» уже есть");
        }

        return $title;
    }
}
