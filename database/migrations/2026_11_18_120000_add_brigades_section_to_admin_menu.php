<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Пункт меню админки ERP «Персонал → Бригады» («Доработки ERP», п. 3). Разделы админки хранятся только в базе
 * (`admin_sections`), поэтому пункт добавляет миграция. Группа «Персонал» берётся та, где уже лежат «Сотрудники»;
 * если её нет — создаётся. Доступ к разделу: главному администратору сразу, остальным — правом
 * `section-personal.brigades:admin`, которое главный администратор создаёт в «Права → Разделы».
 * Повторный запуск ничего не дублирует; отдельно выкладывается через `migrate --path`.
 */
return new class extends Migration {
    public function up(): void
    {
        if (DB::table('admin_sections')->where('section', 'personal.brigades')->exists()) {
            return;
        }
        $parentId = DB::table('admin_sections')->whereIn('section', ['personal.staff', 'personal.users_new', 'personal.admins'])
            ->where('parent_id', '>', 0)->value('parent_id')
            ?? DB::table('admin_sections')->where('section', 'personal')->where('parent_id', 0)->value('id')
            ?? DB::table('admin_sections')->insertGetId([
                'section' => 'personal',
                'title' => json_encode(['ru' => 'Персонал'], JSON_UNESCAPED_UNICODE),
                'page_title' => json_encode(['ru' => 'Персонал'], JSON_UNESCAPED_UNICODE),
                'parent_id' => 0,
                'nav' => 0, // группа только раскрывается, своей страницы у неё нет
                'visible' => 1,
                '_sort' => (int) DB::table('admin_sections')->where('parent_id', 0)->max('_sort') + 1,
            ]);

        DB::table('admin_sections')->insert([
            'section' => 'personal.brigades',
            'title' => json_encode(['ru' => 'Бригады'], JSON_UNESCAPED_UNICODE),
            'page_title' => json_encode(['ru' => 'Бригады'], JSON_UNESCAPED_UNICODE),
            'parent_id' => $parentId,
            'nav' => 1,
            'visible' => 1,
            '_sort' => (int) DB::table('admin_sections')->where('parent_id', $parentId)->max('_sort') + 1,
        ]);
    }

    public function down(): void
    {
        DB::table('admin_sections')->where('section', 'personal.brigades')->delete();
    }
};
