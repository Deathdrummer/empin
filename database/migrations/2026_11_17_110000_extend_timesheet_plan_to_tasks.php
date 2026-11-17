<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * План-график ERP развивается до заданий ТЗ ЭМПИН-pro (раздел 6), а не дублируется новыми таблицами.
 *
 *  timesheet_teams     — исполнитель на дату: бригада (`brigade_id`) или сотрудник. Для бригады `staff_id` — её бригадир,
 *                        чтобы раздел сайта «План-график работ» и чаты объектов работали как раньше. Запись «бригада на дату»
 *                        — внутренняя «смена» ТЗ (П-СМ-02). Прежние записи без `brigade_id` — задания с личным исполнителем.
 *  timesheet_contracts — задание: объект на эту дату + создатель, текст, техника и водитель (снимок на момент назначения,
 *                        П-ОБ-23б). Переписка задания — timesheet_chat (она же чат объекта в мессенджере, п. 1.2 ТЗ).
 * Добавляются: виды работ задания из плана объекта (П-ЗД-11), приглашения с историей (П-ЗД-24), журнал изменений (п. 9.4)
 * и уведомления до ручного закрытия (п. 9.3). Всё новое — с допуском NULL, прежние данные и код продолжают работать.
 */
return new class extends Migration {
    public function up(): void
    {
        // Каждый шаг проверяет, не выполнен ли он: изменения структуры в MySQL не откатываются, и повторный запуск
        // после сбоя на середине должен продолжить, а не упасть
        if (!Schema::hasColumn('timesheet_teams', 'brigade_id')) Schema::table('timesheet_teams', function (Blueprint $table) {
            $table->foreignId('brigade_id')->nullable()->after('staff_id')->constrained('brigades')->restrictOnDelete()->comment('Исполнитель-бригада; NULL — личный исполнитель staff_id');
        });
        $this->addIndexIfMissing('timesheet_teams', 'timesheet_teams_day_index', ['day']);

        if (!Schema::hasColumn('timesheet_contracts', 'creator_staff_id')) Schema::table('timesheet_contracts', function (Blueprint $table) {
            $table->foreignId('creator_staff_id')->nullable()->after('contract_id')->constrained('staff')->restrictOnDelete()->comment('Создатель задания; NULL — записи прототипа и сайта');
            $table->text('text')->nullable()->after('creator_staff_id')->comment('Что сделать');
            // Техника и водитель — снимок на момент назначения: запись сохраняется, даже если машину удалят из ERP (П-ОБ-23б)
            $table->unsignedBigInteger('vehicle_id')->nullable()->comment('vehicles.id на момент назначения');
            $table->string('vehicle_name')->nullable();
            $table->string('vehicle_plate', 20)->nullable();
            $table->unsignedBigInteger('driver_id')->nullable()->comment('drivers.id на момент назначения');
            $table->unsignedBigInteger('driver_staff_id')->nullable()->index();
            $table->string('driver_name')->nullable();
            $table->boolean('driver_assigned')->nullable()->comment('Водитель закреплён за машиной (иначе было предупреждение)');
        });

        if (!Schema::hasTable('timesheet_contract_works')) Schema::create('timesheet_contract_works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timesheet_contract_id')->constrained('timesheet_contracts')->cascadeOnDelete();
            $table->foreignId('work_type_id')->constrained('work_types')->restrictOnDelete();
            $table->decimal('quantity', 14, 3)->nullable();
            $table->unique(['timesheet_contract_id', 'work_type_id'], 'timesheet_contract_works_task_work_unique');
        });

        if (!Schema::hasTable('timesheet_contract_invites')) Schema::create('timesheet_contract_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timesheet_contract_id')->constrained('timesheet_contracts')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->restrictOnDelete()->comment('Приглашённый');
            $table->foreignId('invited_by_staff_id')->constrained('staff')->restrictOnDelete();
            $table->timestamp('invited_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by_staff_id')->nullable()->constrained('staff')->restrictOnDelete();
            $table->index(['staff_id', 'revoked_at']);
        });

        // Журнал изменений: кто, что, когда, было и стало (п. 9.4). Своего журнала в ERP нет
        if (!Schema::hasTable('audit_log')) Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 32)->comment('task, …');
            $table->unsignedBigInteger('entity_id');
            $table->string('action', 48);
            $table->foreignId('actor_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['entity_type', 'entity_id']);
            $table->index(['actor_staff_id', 'created_at']);
        });

        // Уведомления в приложении, которые висят до ручного закрытия (п. 9.3). Остаются у получателя,
        // даже если доступа к заданию больше нет (П-ЗД-32), поэтому без внешнего ключа на задание
        if (!Schema::hasTable('staff_notifications')) Schema::create('staff_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->restrictOnDelete();
            $table->string('kind', 48);
            $table->string('title');
            $table->text('body')->nullable();
            $table->unsignedBigInteger('task_id')->nullable()->comment('timesheet_contracts.id на момент события');
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('closed_at')->nullable()->comment('Закрыто пользователем');
            $table->timestamp('withdrawn_at')->nullable()->comment('Снято системой');
            $table->index(['staff_id', 'closed_at', 'withdrawn_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_notifications');
        Schema::dropIfExists('audit_log');
        Schema::dropIfExists('timesheet_contract_invites');
        Schema::dropIfExists('timesheet_contract_works');
        Schema::table('timesheet_contracts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('creator_staff_id');
            $table->dropColumn(['text', 'vehicle_id', 'vehicle_name', 'vehicle_plate', 'driver_id', 'driver_staff_id', 'driver_name', 'driver_assigned']);
        });
        Schema::table('timesheet_teams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brigade_id');
        });
    }

    private function addIndexIfMissing(string $table, string $name, array $columns): void
    {
        $exists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', $table)
            ->where('index_name', $name)
            ->exists();
        if (!$exists) {
            Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
        }
    }
};
