<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Одна учётная запись приложения на сотрудника ERP (П-ОБ-21; находка 6): уникальный ключ на `users.staff_id`.
 * На проде 17.11.2026 повторов не было. Если они появились, миграция останавливается и перечисляет сотрудников —
 * лишние учётные записи разбирает администратор, автоматически ничего не удаляется.
 */
return new class extends Migration {
    private const INDEX = 'users_staff_id_unique';

    public function up(): void
    {
        if ($this->hasIndex()) {
            return;
        }
        $duplicates = DB::table('users')->whereNotNull('staff_id')
            ->select('staff_id', DB::raw('COUNT(*) AS accounts'))
            ->groupBy('staff_id')->havingRaw('COUNT(*) > 1')
            ->pluck('accounts', 'staff_id');
        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException('У сотрудников несколько учётных записей (staff_id: количество): '
                .$duplicates->map(fn ($n, $id) => "{$id}: {$n}")->implode(', '));
        }
        Schema::table('users', fn (Blueprint $table) => $table->unique('staff_id', self::INDEX));
    }

    public function down(): void
    {
        if ($this->hasIndex()) {
            // Внешнему ключу staff_id нужен индекс: MySQL мог убрать прежний, когда появился уникальный
            if (!$this->hasIndex('users_staff_id_foreign')) {
                Schema::table('users', fn (Blueprint $table) => $table->index('staff_id', 'users_staff_id_foreign'));
            }
            Schema::table('users', fn (Blueprint $table) => $table->dropUnique(self::INDEX));
        }
    }

    private function hasIndex(string $name = self::INDEX): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'users')
            ->where('index_name', $name)
            ->exists();
    }
};
