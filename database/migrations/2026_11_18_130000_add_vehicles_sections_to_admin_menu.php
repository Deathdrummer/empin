<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Пункты меню админки ERP «Производство → Техника» и «Производство → Водители» («Доработки ERP», п. 4).
 * Разделы админки хранятся только в базе (`admin_sections`), поэтому их добавляет миграция; группа «Производство» —
 * для справочников ЭМПИН-pro (сюда же войдут «Виды работ»). Доступ: главному администратору сразу, остальным — правами
 * `section-production.vehicles:admin` и `section-production.drivers:admin` («Права → Разделы»).
 * Повторный запуск ничего не дублирует; отдельно выкладывается через `migrate --path`.
 */
return new class extends Migration {
    private const ITEMS = [
        'production.vehicles' => 'Техника',
        'production.drivers' => 'Водители',
    ];

    public function up(): void
    {
        $parentId = DB::table('admin_sections')->where('section', 'production')->where('parent_id', 0)->value('id')
            ?? DB::table('admin_sections')->insertGetId([
                'section' => 'production',
                'title' => json_encode(['ru' => 'Производство'], JSON_UNESCAPED_UNICODE),
                'page_title' => json_encode(['ru' => 'Производство'], JSON_UNESCAPED_UNICODE),
                'parent_id' => 0,
                'nav' => 0, // группа только раскрывается, своей страницы у неё нет
                'visible' => 1,
                '_sort' => (int) DB::table('admin_sections')->where('parent_id', 0)->max('_sort') + 1,
            ]);

        foreach (self::ITEMS as $section => $title) {
            if (DB::table('admin_sections')->where('section', $section)->exists()) {
                continue;
            }
            DB::table('admin_sections')->insert([
                'section' => $section,
                'title' => json_encode(['ru' => $title], JSON_UNESCAPED_UNICODE),
                'page_title' => json_encode(['ru' => $title], JSON_UNESCAPED_UNICODE),
                'parent_id' => $parentId,
                'nav' => 1,
                'visible' => 1,
                '_sort' => (int) DB::table('admin_sections')->where('parent_id', $parentId)->max('_sort') + 1,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('admin_sections')->whereIn('section', array_keys(self::ITEMS))->delete();
        $parentId = DB::table('admin_sections')->where('section', 'production')->where('parent_id', 0)->value('id');
        if ($parentId && !DB::table('admin_sections')->where('parent_id', $parentId)->exists()) {
            DB::table('admin_sections')->whereKey($parentId)->delete();
        }
    }
};
