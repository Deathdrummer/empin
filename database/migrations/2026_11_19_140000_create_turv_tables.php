<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ТУРВ (ТЗ, раздел 7): порядок бригад и строк за учётной записью (П-СМ-33) и «Изменено <кем>» после корректировки
 * выхода в табеле (П-СМ-13, П-СМ-15в)
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('turv_orders')) Schema::create('turv_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->unique()->constrained('staff')->cascadeOnDelete();
            $table->json('brigade_ids')->nullable()->comment('Порядок бригадных разделов');
            $table->json('rows')->nullable()->comment('Порядок строк по разделам: b<id>, drivers, personal');
            $table->timestamps();
        });
        if (!Schema::hasColumn('attendance_shifts', 'corrected_by_staff_id')) Schema::table('attendance_shifts', function (Blueprint $table) {
            $table->foreignId('corrected_by_staff_id')->nullable()->after('confirmed_offline')->constrained('staff')->restrictOnDelete();
            $table->timestamp('corrected_at')->nullable()->after('corrected_by_staff_id');
        });
        if (!Schema::hasColumn('attendance_marks', 'corrected_by_staff_id')) Schema::table('attendance_marks', function (Blueprint $table) {
            $table->foreignId('corrected_by_staff_id')->nullable()->after('after_confirmation')->constrained('staff')->restrictOnDelete()->comment('Выход водителя исправлен в табеле');
            $table->timestamp('corrected_at')->nullable()->after('corrected_by_staff_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turv_orders');
        foreach (['attendance_shifts', 'attendance_marks'] as $name) {
            if (Schema::hasColumn($name, 'corrected_by_staff_id')) Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('corrected_by_staff_id');
                $table->dropColumn('corrected_at');
            });
        }
    }
};
