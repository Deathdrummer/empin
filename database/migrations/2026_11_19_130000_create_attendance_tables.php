<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Выход на смену (ТЗ, раздел 7; интервью 8.1–8.12): самоотметки, смена бригады на дату с заявкой состава и
 * подтверждением, засчитанные рабочие дни — из них строится ТУРВ
 */
return new class extends Migration {
    public function up(): void
    {
        // Самоотметка: одна на сотрудника в день — в бригаде или водителем (П-СМ-04, П-СМ-20а)
        if (!Schema::hasTable('attendance_marks')) Schema::create('attendance_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->restrictOnDelete();
            $table->date('work_date');
            $table->boolean('as_driver')->default(false);
            $table->foreignId('brigade_id')->nullable()->constrained('brigades')->restrictOnDelete()->comment('NULL — водителем');
            $table->timestamp('pressed_at')->nullable()->comment('Время нажатия на телефоне');
            $table->timestamp('received_at')->nullable();
            $table->boolean('offline')->default(false)->comment('Сделана без связи');
            $table->boolean('time_unverified')->default(false)->comment('Часы телефона расходятся с серверными больше чем на 5 минут');
            $table->boolean('after_confirmation')->default(false)->comment('Пришла после подтверждения: состав не меняет');
            $table->string('client_uuid', 64)->nullable()->unique();
            $table->timestamps();
            $table->unique(['staff_id', 'work_date']);
            $table->index(['brigade_id', 'work_date']);
        });

        // Смена бригады на дату (П-СМ-02): заявка состава и подтверждение
        if (!Schema::hasTable('attendance_shifts')) Schema::create('attendance_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brigade_id')->constrained('brigades')->restrictOnDelete();
            $table->date('work_date');
            $table->json('crew_staff_ids')->nullable()->comment('Заявка состава; NULL — не заявлялся');
            $table->foreignId('crew_by_staff_id')->nullable()->constrained('staff')->restrictOnDelete();
            $table->timestamp('crew_at')->nullable();
            $table->foreignId('confirmed_by_staff_id')->nullable()->constrained('staff')->restrictOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->boolean('confirmed_offline')->default(false);
            $table->timestamps();
            $table->unique(['brigade_id', 'work_date']);
        });

        // Засчитанный рабочий день — идёт в ТУРВ; у сотрудника один на дату (П-СМ-17, П-СМ-20а)
        if (!Schema::hasTable('attendance_days')) Schema::create('attendance_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->restrictOnDelete();
            $table->date('work_date');
            $table->boolean('as_driver')->default(false);
            $table->foreignId('brigade_id')->nullable()->constrained('brigades')->restrictOnDelete();
            $table->string('source', 16)->comment('confirmed — подтверждение в задании, corrected — корректировка в ТУРВ');
            $table->foreignId('shift_id')->nullable()->constrained('attendance_shifts')->nullOnDelete();
            $table->foreignId('by_staff_id')->constrained('staff')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['staff_id', 'work_date']);
            $table->index(['brigade_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_days');
        Schema::dropIfExists('attendance_shifts');
        Schema::dropIfExists('attendance_marks');
    }
};
