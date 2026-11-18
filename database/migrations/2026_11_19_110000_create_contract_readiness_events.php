<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * История готовности объекта (ТЗ П-ФК-34): каждое изменение — прежнее и новое значение, источник, кто, когда.
 * Источник `erp` — изменение в окне «Производство» договора (решение 19.11.2026); источники приложения (согласование
 * отчёта, прямое изменение и др., П-ФК-32) добавит раздел учёта работ. Актуальное значение — в `contract_production_info`.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('contract_readiness_events')) {
            return;
        }
        Schema::create('contract_readiness_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->decimal('percent_before', 5, 2)->nullable();
            $table->decimal('percent_after', 5, 2);
            $table->string('source', 32)->comment('erp — окно «Производство» ERP');
            $table->unsignedBigInteger('admin_id')->nullable()->comment('Администратор ERP (admin_users)');
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete()->comment('Сотрудник в приложении');
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['contract_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_readiness_events');
    }
};
