<?php

use App\Production\Rights\Right;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Права приложения ЭМПИН-pro в действующем механизме прав ERP (ТЗ, п. 4.2.2; «Доработки ERP», п. 6).
 *
 * 19 прав — обычные права ERP (Spatie, guard `site`) в своей группе: выдаются на том же экране прав сотрудника и
 * приходят в приложение тем же списком `permissions`. ERP дополняется настройками права (глубина, область, даты;
 * несколько настроек на одно право — интервью, 8.18, FR-359) и историей выдачи и отзыва (кто, кому, что, когда).
 */
return new class extends Migration {
    private const GROUP_NAME = "ПРАВА ПРИЛОЖЕНИЯ\nЭМПИН-PRO (ПРОИЗВОДСТВО)";

    public function up(): void
    {
        // На продакшене `permissions.title` есть (сверено 17.11.2026), а миграция репозитория её не создаёт —
        // добавляем только там, где её нет (локальная и тестовая база)
        if (!Schema::hasColumn('permissions', 'title')) {
            Schema::table('permissions', fn (Blueprint $table) => $table->string('title')->nullable()->after('name'));
        }

        $groupId = $this->ensureGroup();
        $now = now();
        foreach (Right::cases() as $right) {
            $exists = DB::table('permissions')->where('name', $right->permissionName())->where('guard_name', 'site')->exists();
            if ($exists) {
                // Ранняя версия писала названия с префиксом «ЭМПИН-pro: »; группа прав и так подписана — префикс не нужен
                DB::table('permissions')->where('name', $right->permissionName())->where('guard_name', 'site')
                    ->where('title', 'ЭМПИН-pro: ' . $right->title())->update(['title' => $right->title()]);
            } else {
                DB::table('permissions')->insert([
                    'name' => $right->permissionName(),
                    'title' => $right->title(),
                    'guard_name' => 'site',
                    'group' => $groupId,
                    'sort' => $right->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        Schema::create('permission_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->unsignedSmallInteger('depth_days')->nullable()->comment('Глубина N дней, считая сегодня; NULL — без ограничений');
            $table->boolean('scope_all')->default(false)->comment('Без ограничений: все задания / все бригады / вся компания');
            $table->json('staff_ids')->nullable()->comment('Выбранные сотрудники: создатели, начальники участков или люди');
            $table->json('brigade_ids')->nullable()->comment('Выбранные бригады');
            $table->boolean('all_brigades')->default(false)->comment('Право 19: все бригады');
            $table->date('date_from')->nullable()->comment('Право 19: просмотр с даты');
            $table->date('date_to')->nullable()->comment('Право 19: просмотр по дату');
            $table->timestamps();
            $table->index(['user_id', 'permission_id']);
        });

        // История выдачи, отзыва и изменения прав: кто, кому, что и когда («Доработки ERP», п. 6; П-ПР-25)
        Schema::create('permission_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->comment('Кому (учётная запись сотрудника)');
            $table->unsignedBigInteger('role_id')->nullable()->comment('Роль, если менялась роль или её права');
            $table->unsignedBigInteger('permission_id')->nullable();
            $table->string('action', 32)->comment('granted / revoked / role_set / role_converted / settings_changed / role_permission_granted / role_permission_revoked');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->unsignedBigInteger('by_admin_id')->nullable()->comment('Кто: администратор ERP');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'created_at']);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_events');
        Schema::dropIfExists('permission_settings');
        DB::table('permissions')->where('guard_name', 'site')->whereIn('name', array_map(fn (Right $r) => $r->permissionName(), Right::cases()))->delete();
        $groups = $this->groups();
        $groups = array_values(array_filter($groups, fn ($g) => ($g['name'] ?? null) !== self::GROUP_NAME));
        DB::table('settings')->where('key', 'permissions_groups')->update(['value' => json_encode($groups, JSON_UNESCAPED_UNICODE)]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** Группа прав в настройке ERP `permissions_groups`: номер — первый свободный (на продакшене 17.11.2026 — 10) */
    private function ensureGroup(): int
    {
        $groups = $this->groups();
        foreach ($groups as $group) {
            if (($group['name'] ?? null) === self::GROUP_NAME) {
                return (int) $group['id'];
            }
        }
        $usedIds = array_map(fn ($g) => (int) ($g['id'] ?? 0), $groups);
        $usedIds[] = (int) DB::table('permissions')->max('group');
        $id = max($usedIds ?: [0]) + 1;
        $siteSorts = array_map(fn ($g) => (int) ($g['sort'] ?? 0), array_filter($groups, fn ($g) => ($g['group'] ?? null) === 'site'));
        $groups[] = ['id' => $id, 'name' => self::GROUP_NAME, 'group' => 'site', 'cols_count' => 2, 'sort' => max($siteSorts ?: [0]) + 1];

        $json = json_encode($groups, JSON_UNESCAPED_UNICODE);
        DB::table('settings')->where('key', 'permissions_groups')->exists()
            ? DB::table('settings')->where('key', 'permissions_groups')->update(['value' => $json])
            : DB::table('settings')->insert(['key' => 'permissions_groups', 'value' => $json]);

        return $id;
    }

    private function groups(): array
    {
        $value = DB::table('settings')->where('key', 'permissions_groups')->value('value');
        $groups = $value ? json_decode($value, true) : [];

        return is_array($groups) ? $groups : [];
    }
};
