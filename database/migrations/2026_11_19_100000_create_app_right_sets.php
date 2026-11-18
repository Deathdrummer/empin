<?php

use App\Production\Rights\Right;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/** Типовые наборы прав приложения по должностям (ТЗ, п. 4.2.3; П-ПР-26; «Доработки ERP», п. 6) */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('role_permission_settings')) {
            Schema::create('role_permission_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
                $table->unsignedSmallInteger('depth_days')->nullable();
                $table->boolean('scope_all')->default(false);
                $table->json('staff_ids')->nullable();
                $table->json('brigade_ids')->nullable();
                $table->boolean('all_brigades')->default(false);
                $table->date('date_from')->nullable();
                $table->date('date_to')->nullable();
                $table->timestamps();
                $table->index(['role_id', 'permission_id']);
            });
        }

        $all = ['scope_all' => true];
        $sets = [
            'app-set-director' => ['Генеральный директор / Главный инженер', [
                [Right::TaskViewAll, [$all]],
                [Right::TimesheetView, [$all, ['all_brigades' => true]]],
            ]],
            // Глубина прав 2 и 3 «задаётся в ERP», бригады права 19 — свои у каждого: задаются при применении
            'app-set-site-manager' => ['Начальник участка', [
                [Right::TaskCreate, []], [Right::TaskEditOwn, []], [Right::TaskDeleteOwn, []], [Right::TaskInvite, []],
                [Right::ReportSubmit, []], [Right::ReportEditApproved, []], [Right::OverageDeclare, []], [Right::OverageApprove, []],
                [Right::OffPlanDeclare, []], [Right::OffPlanApprove, []], [Right::TimesheetView, []],
            ]],
            'app-set-foreman' => ['Бригадир', [
                [Right::ReportSubmit, []], [Right::OverageDeclare, []], [Right::OffPlanDeclare, []], [Right::CrewDeclare, []],
            ]],
            'app-set-electrician' => ['Электромонтажник', []],
            'app-set-driver' => ['Водитель спецтехники', []],
            'app-set-admin' => ['Администратор', [
                [Right::AttendanceCorrect, [['scope_all' => true, 'depth_days' => null]]],
                [Right::TimesheetView, [$all, ['all_brigades' => true]]],
                [Right::ReadinessEdit, []],
                [Right::TaskViewAll, [$all]],
            ]],
        ];

        $now = now();
        // На проде у ролей есть порядок `sort` (его использует раздел «Роли»), миграции репозитория его не создают
        $withSort = Schema::hasColumn('roles', 'sort');
        $sort = $withSort ? (int) DB::table('roles')->max('sort') : 0;
        foreach ($sets as $name => [$title, $rights]) {
            if (DB::table('roles')->where('name', $name)->where('guard_name', 'site')->exists()) {
                continue;
            }
            $roleId = DB::table('roles')->insertGetId(['name' => $name, 'title' => $title, 'guard_name' => 'site', 'created_at' => $now, 'updated_at' => $now]
                + ($withSort ? ['sort' => ++$sort] : []));
            foreach ($rights as [$right, $grants]) {
                $permissionId = DB::table('permissions')->where('name', $right->permissionName())->where('guard_name', 'site')->value('id');
                if (!$permissionId) {
                    continue;
                }
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
                foreach ($grants as $grant) {
                    DB::table('role_permission_settings')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                        'depth_days' => $grant['depth_days'] ?? null,
                        'scope_all' => $grant['scope_all'] ?? false,
                        'staff_ids' => json_encode([]),
                        'brigade_ids' => json_encode([]),
                        'all_brigades' => $grant['all_brigades'] ?? false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('roles')->where('guard_name', 'site')->where('name', 'like', 'app-set-%')->delete();
        Schema::dropIfExists('role_permission_settings');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
