<?php

namespace App\Production\Reports;

use App\Models\ContractReadinessEvent;
use App\Production\Erp\ErpGateway;
use App\Production\Support\ProductionException;

/**
 * Актуальная готовность объекта и её история (П-ФК-32…35): последнее применённое значение хранится у договора в ERP
 * (одна база — значение сразу в ERP, П-ФК-35), каждое изменение — событие с источником, автором и временем
 */
final class ObjectReadiness
{
    public function __construct(private readonly ErpGateway $erp)
    {
    }

    public function current(int $contractId): ?float
    {
        return $this->erp->storedReadiness($contractId);
    }

    /**
     * Применить новое значение. $shown — значение, которое видел пользователь: если с тех пор готовность изменили,
     * новое не затирает чужое (как в окне ERP). Вызывать внутри транзакции.
     *
     * @param array{staff_id?: ?int, admin_id?: ?int, reason?: ?string, report_id?: ?int, task_id?: ?int, details?: ?array} $meta
     */
    public function apply(int $contractId, ?float $percent, string $source, array $meta, bool $checkShown = false, ?float $shown = null, bool $recordUnchanged = false): ?ContractReadinessEvent
    {
        $was = $this->erp->storedReadiness($contractId, lock: true);
        if ($checkShown && $was !== $shown) {
            throw ProductionException::invalid('Готовность уже изменена: '.self::text($was), 'readiness_changed');
        }
        if ($was === $percent && !$recordUnchanged) {
            return null;
        }
        if ($was !== $percent) {
            $this->erp->storeReadiness($contractId, $percent);
        }

        return ContractReadinessEvent::create([
            'contract_id' => $contractId,
            'percent_before' => $was,
            'percent_after' => $percent,
            'source' => $source,
            'admin_id' => $meta['admin_id'] ?? null,
            'staff_id' => $meta['staff_id'] ?? null,
            'reason' => $meta['reason'] ?? null,
            'report_id' => $meta['report_id'] ?? null,
            'task_id' => $meta['task_id'] ?? null,
            'details' => $meta['details'] ?? null,
            'created_at' => now(),
        ]);
    }

    /**
     * Готовность установлена этим отчётом — согласованием или применением исправленного процента, и после него
     * другим событием не менялась (П-ФК-40). Возвращает событие отчёта или null.
     */
    public function setByReport(int $contractId, int $reportId): ?ContractReadinessEvent
    {
        $last = ContractReadinessEvent::where('contract_id', $contractId)
            ->where(fn ($q) => $q->whereColumn('percent_before', '<>', 'percent_after')
                ->orWhereNull('percent_before')->orWhereNull('percent_after'))
            ->orderByDesc('id')
            ->first();
        if (!$last || (int) $last->report_id !== $reportId) {
            return null;
        }

        return in_array($last->source, [ContractReadinessEvent::SOURCE_REPORT_APPROVED, ContractReadinessEvent::SOURCE_REPORT_EDIT], true) ? $last : null;
    }

    /** Процент 0–100 до двух знаков; пусто — null */
    public static function parse(mixed $value, string $message = 'Процент — число от 0 до 100'): ?float
    {
        if ($value === null) {
            return null;
        }
        $text = str_replace([' ', ',', '%'], ['', '.', ''], trim((string) $value));
        if ($text === '') {
            return null;
        }
        if (!is_numeric($text) || (float) $text < 0 || (float) $text > 100) {
            throw ProductionException::invalid($message);
        }

        return round((float) $text, 2);
    }

    public static function text(?float $percent): string
    {
        return $percent === null ? 'не задана' : rtrim(rtrim(number_format($percent, 2, ',', ' '), '0'), ',').' %';
    }
}
