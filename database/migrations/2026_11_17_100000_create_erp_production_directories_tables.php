<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Справочники ERP для производственного приложения («Доработки ERP», пп. 1–5; ТЗ, п. 10.1) */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('brigades', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Наименование бригады');
            // Бригадиром можно быть только в одной бригаде (П-ОБ-17)
            $table->foreignId('foreman_staff_id')->nullable()->unique()->constrained('staff')->restrictOnDelete()->comment('Текущий бригадир');
            $table->date('disbanded_on')->nullable()->comment('Дата расформирования');
            $table->timestamps();
        });

        Schema::create('brigade_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brigade_id')->constrained('brigades')->restrictOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->restrictOnDelete();
            $table->date('joined_on')->comment('Первый день в бригаде');
            $table->date('left_on')->nullable()->comment('Дата выхода: с этого дня уже не состоит');
            $table->timestamps();
            $table->index(['staff_id', 'left_on']);
            $table->index(['brigade_id', 'left_on']);
        });

        Schema::create('contract_production_info', function (Blueprint $table) {
            $table->foreignId('contract_id')->primary()->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('responsible_staff_id')->nullable()->constrained('staff')->nullOnDelete()->comment('Ответственный начальник участка');
            // Источник значения — приложение; ERP хранит и отдаёт обратно (п. 10.3, правило 7)
            $table->decimal('readiness_percent', 5, 2)->nullable()->comment('Утверждённая готовность объекта, %');
            $table->timestamp('readiness_changed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('contract_brigades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('brigade_id')->constrained('brigades')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['contract_id', 'brigade_id']);
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Наименование (тип)');
            $table->string('plate', 20)->nullable()->comment('Госномер');
            $table->boolean('not_used')->default(false)->comment('Не используется: ремонт, продажа');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->unique()->constrained('staff')->restrictOnDelete();
            $table->boolean('not_used')->default(false)->comment('Не используется: уволен, отстранён');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('vehicle_drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['vehicle_id', 'driver_id']);
        });

        Schema::create('work_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit', 20)->comment('Единица измерения');
            $table->timestamps();
            $table->softDeletes();
        });

        // План объекта: каждое изменение — новая редакция, прежние сохраняются (п. 5 «Доработок ERP»)
        Schema::create('contract_plan_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('created_by_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();
            $table->index(['contract_id', 'id']);
        });

        Schema::create('contract_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revision_id')->constrained('contract_plan_revisions')->cascadeOnDelete();
            $table->foreignId('work_type_id')->constrained('work_types')->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->unique(['revision_id', 'work_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_plan_items');
        Schema::dropIfExists('contract_plan_revisions');
        Schema::dropIfExists('work_types');
        Schema::dropIfExists('vehicle_drivers');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('contract_brigades');
        Schema::dropIfExists('contract_production_info');
        Schema::dropIfExists('brigade_members');
        Schema::dropIfExists('brigades');
    }
};
