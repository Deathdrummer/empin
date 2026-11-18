<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * У бригады одна запись план-графика на дату — её смена (ТЗ, П-ЗД-10, П-ЗД-41: выход бригады на дату один, сколько бы
 * заданий ни было)
 */
return new class extends Migration {
    public function up(): void
    {
        $exists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', 'timesheet_teams')
            ->where('index_name', 'timesheet_teams_brigade_day_unique')
            ->exists();
        if (!$exists) {
            Schema::table('timesheet_teams', fn (Blueprint $table) => $table->unique(['brigade_id', 'day'], 'timesheet_teams_brigade_day_unique'));
        }
    }

    public function down(): void
    {
        Schema::table('timesheet_teams', fn (Blueprint $table) => $table->dropUnique('timesheet_teams_brigade_day_unique'));
    }
};
