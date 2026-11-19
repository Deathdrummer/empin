<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Изменение готовности объекта (ТЗ П-ФК-34): было → стало, источник, кто (администратор ERP или сотрудник), когда.
 * История — данные приложения (источник готовности — приложение, п. 10.3.7); само значение ERP хранит у договора
 * («Доработки ERP», п. 1) и получает его через шлюз ERP
 */
class ContractReadinessEvent extends Model
{
    // Источники по перечню П-ФК-32 и окно «Производство» ERP (решение 19.11.2026)
    public const SOURCE_ERP = 'erp';
    public const SOURCE_REPORT_APPROVED = 'report_approved';
    public const SOURCE_REPORT_EDIT = 'report_edit';
    public const SOURCE_DIRECT = 'direct';
    public const SOURCE_TASK_DELETED = 'task_deleted';
    public const SOURCE_OBJECT_CHANGE = 'object_change';

    public const UPDATED_AT = null;

    protected $table = 'contract_readiness_events';

    protected $guarded = ['id'];

    protected $casts = [
        'percent_before' => 'decimal:2',
        'percent_after' => 'decimal:2',
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    public static function sourceTitle(string $source): string
    {
        return match ($source) {
            self::SOURCE_ERP => 'ERP',
            self::SOURCE_REPORT_APPROVED => 'Согласование отчёта',
            self::SOURCE_REPORT_EDIT => 'Правка согласованного отчёта',
            self::SOURCE_DIRECT => 'Прямое изменение',
            self::SOURCE_TASK_DELETED => 'Удаление задания',
            self::SOURCE_OBJECT_CHANGE => 'Смена объекта в задании',
            default => $source,
        };
    }
}
