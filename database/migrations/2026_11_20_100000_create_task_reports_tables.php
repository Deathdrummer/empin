<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Отчёты по заданиям (ТЗ, раздел 8): отчёт, позиции «вид работ — количество» с заявленным и согласованным раздельно,
 * фото; источник события готовности объекта — отчёт или задание
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('task_reports')) Schema::create('task_reports', function (Blueprint $table) {
            $table->id();
            // Один отчёт на задание (П-ФК-02). Удаляется только удалением задания в API заданий — с журналом и выбором по
            // готовности (П-ФК-39, П-ФК-40); старый план-график сайта и прототипа задание с отчётом не удалит
            $table->foreignId('task_id')->unique()->constrained('timesheet_contracts')->restrictOnDelete();
            $table->string('status', 16)->comment('draft, submitted, approved, rejected');
            $table->foreignId('author_staff_id')->constrained('staff')->restrictOnDelete()->comment('Первоначальный создатель');
            $table->text('text')->nullable();
            $table->decimal('task_percent_declared', 5, 2)->nullable();
            $table->decimal('task_percent_approved', 5, 2)->nullable();
            $table->decimal('object_percent_prefilled', 5, 2)->nullable()->comment('Готовность объекта, подставленная в поле (П-ФК-35а)');
            $table->decimal('object_percent_declared', 5, 2)->nullable();
            $table->decimal('object_percent_approved', 5, 2)->nullable();
            $table->foreignId('submitted_by_staff_id')->nullable()->constrained('staff')->restrictOnDelete()->comment('Кто официально заявил');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('decided_by_staff_id')->nullable()->constrained('staff')->restrictOnDelete()->comment('Кто согласовал или отклонил');
            $table->timestamp('decided_at')->nullable();
            $table->text('reject_comment')->nullable();
            $table->foreignId('edited_by_staff_id')->nullable()->constrained('staff')->restrictOnDelete()->comment('Последняя правка согласованного (П-ФК-27)');
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('author_locked_at')->nullable()->comment('Сменился исполнитель: автор больше не правит (П-ЗД-33)');
            $table->unsignedInteger('revision')->default(1)->comment('Номер редакции: согласование проверяет, что видели последнюю');
            $table->string('client_uuid', 36)->nullable()->unique()->comment('Метка создания с телефона: повтор без связи не создаёт второй');
            $table->timestamps();
            $table->index('status');
        });

        if (!Schema::hasTable('task_report_items')) Schema::create('task_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('task_reports')->cascadeOnDelete();
            $table->foreignId('work_type_id')->constrained('work_types')->restrictOnDelete();
            // Заявленное и согласованное раздельно (П-ФК-15)
            $table->decimal('quantity_declared', 14, 3)->nullable();
            $table->decimal('quantity_approved', 14, 3)->nullable();
            $table->foreignId('edited_by_staff_id')->nullable()->constrained('staff')->restrictOnDelete();
            $table->timestamp('edited_at')->nullable();
            // Отнесение согласованного количества к плану и допам по хронологии (П-ФК-23, п. 11.2)
            $table->unsignedBigInteger('plan_revision_id')->nullable()->comment('Редакция плана, по которой отнесено');
            $table->decimal('quantity_in_plan', 14, 3)->nullable();
            $table->decimal('quantity_extra', 14, 3)->nullable();
            $table->string('extra_kind', 16)->nullable()->comment('overage — сверх проекта, off_plan — вне плана');
            $table->unique(['report_id', 'work_type_id']);
            $table->index('work_type_id');
        });

        if (!Schema::hasTable('task_report_photos')) Schema::create('task_report_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('task_reports')->cascadeOnDelete();
            $table->string('path');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->foreignId('added_by_staff_id')->constrained('staff')->restrictOnDelete();
            $table->string('client_uuid', 36)->nullable()->unique();
            $table->timestamp('created_at')->nullable();
        });

        if (!Schema::hasColumn('contract_readiness_events', 'report_id')) Schema::table('contract_readiness_events', function (Blueprint $table) {
            // Без внешних ключей: событие остаётся в истории и после удаления задания с отчётом
            $table->unsignedBigInteger('report_id')->nullable()->after('reason');
            $table->unsignedBigInteger('task_id')->nullable()->after('report_id');
            $table->json('details')->nullable()->after('task_id');
        });
        // «Вернуть прежнюю» может вернуть «не задана»
        DB::statement('ALTER TABLE contract_readiness_events MODIFY percent_after DECIMAL(5,2) NULL');
    }

    public function down(): void
    {
        Schema::table('contract_readiness_events', function (Blueprint $table) {
            $table->dropColumn(['report_id', 'task_id', 'details']);
        });
        Schema::dropIfExists('task_report_photos');
        Schema::dropIfExists('task_report_items');
        Schema::dropIfExists('task_reports');
    }
};
