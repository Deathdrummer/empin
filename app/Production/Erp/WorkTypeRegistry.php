<?php

namespace App\Production\Erp;

use App\Models\Erp\WorkType;
use App\Production\Support\ProductionException;
use Illuminate\Support\Facades\DB;

/**
 * Общий справочник видов работ в ERP («Доработки ERP», п. 5; ТЗ П-ОБ-29, П-ОБ-33; интервью 4.3, BR9-046, ERP9-005/011):
 * наименование и единица измерения. Новые виды работ появляются только здесь — в приложении их не завести.
 *
 * Удаление мягкое: вид работ остаётся в прежних планах и заданиях, номер записи не выдаётся повторно. Единицу
 * измерения уже использованного вида работ не меняем: количества в планах и заданиях записаны в ней (вывод: иначе
 * «450 м» молча станут «450 км»). Каждое изменение — в журнале.
 */
class WorkTypeRegistry
{
    public const ENTITY = 'work_type';

    public function create(string $name, string $unit): WorkType
    {
        $data = ['name' => $this->name($name), 'unit' => $this->unit($unit)];
        $this->assertNameFree($data['name']);
        $workType = WorkType::create($data);
        ErpAudit::write(self::ENTITY, $workType->id, 'created', null, $data);

        return $workType;
    }

    public function update(WorkType $workType, string $name, string $unit): void
    {
        $after = ['name' => $this->name($name), 'unit' => $this->unit($unit)];
        $before = ['name' => $workType->name, 'unit' => $workType->unit];
        if ($after === $before) {
            return;
        }
        $this->assertNameFree($after['name'], $workType->id);
        if ($after['unit'] !== $before['unit'] && $this->isUsed($workType->id)) {
            throw ProductionException::invalid("Единицу измерения не изменить: вид работ уже есть в планах или заданиях в «{$before['unit']}». Заведите новый вид работ");
        }
        $workType->update($after);
        ErpAudit::write(self::ENTITY, $workType->id, 'updated', $before, $after);
    }

    /**
     * Удалить из справочника (мягко). Пока вид работ стоит в действующем плане активного объекта, его оттуда
     * предлагают в задания (П-ЗД-11) — сначала его убирают из плана
     */
    public function delete(WorkType $workType): void
    {
        $objects = $this->usage([$workType->id])[$workType->id]['plans'] ?? 0;
        if ($objects > 0) {
            $where = $this->oneObject($objects) ? "в действующем плане {$objects} объекта" : "в действующих планах {$objects} объектов";
            throw ProductionException::invalid("Вид работ есть {$where}: сначала уберите его из плана");
        }
        $workType->delete();
        ErpAudit::write(self::ENTITY, $workType->id, 'deleted', ['name' => $workType->name, 'unit' => $workType->unit], null);
    }

    /**
     * Где используется: в скольких действующих планах активных объектов и в скольких заданиях
     *
     * @param int[] $ids
     * @return array<int, array{plans: int, tasks: int}>
     */
    public function usage(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $current = DB::table('contract_plan_revisions')->selectRaw('MAX(id) as id')->groupBy('contract_id');
        $plans = DB::table('contract_plan_items as i')
            ->joinSub($current, 'cur', 'cur.id', '=', 'i.revision_id')
            ->join('contract_plan_revisions as r', 'r.id', '=', 'i.revision_id')
            ->join('contracts as c', 'c.id', '=', 'r.contract_id')
            ->where('c.archive', 0)
            ->whereIn('i.work_type_id', $ids)
            ->groupBy('i.work_type_id')
            ->selectRaw('i.work_type_id, COUNT(DISTINCT r.contract_id) as total')
            ->pluck('total', 'work_type_id');
        $tasks = DB::table('timesheet_contract_works')->whereIn('work_type_id', $ids)
            ->groupBy('work_type_id')->selectRaw('work_type_id, COUNT(DISTINCT timesheet_contract_id) as total')
            ->pluck('total', 'work_type_id');

        $result = [];
        foreach ($ids as $id) {
            $result[$id] = ['plans' => (int) ($plans[$id] ?? 0), 'tasks' => (int) ($tasks[$id] ?? 0)];
        }

        return $result;
    }

    /** Есть ли вид работ хоть в одной редакции плана (в том числе прежней) или в задании */
    public function isUsed(int $id): bool
    {
        return in_array($id, $this->usedIds([$id]), true);
    }

    /**
     * Какие из видов работ уже использованы — одним запросом на весь список
     *
     * @param int[] $ids
     * @return int[]
     */
    public function usedIds(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $plans = DB::table('contract_plan_items')->whereIn('work_type_id', $ids)->distinct()->pluck('work_type_id');
        $tasks = DB::table('timesheet_contract_works')->whereIn('work_type_id', $ids)->distinct()->pluck('work_type_id');

        return $plans->merge($tasks)->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    private function assertNameFree(string $name, ?int $exceptId = null): void
    {
        $taken = WorkType::where('name', $name)->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))->first();
        if ($taken) {
            throw ProductionException::invalid("Вид работ «{$taken->name}» уже есть в справочнике ({$taken->unit})");
        }
    }

    private function name(string $name): string
    {
        $name = mb_substr(trim(preg_replace('/\s+/u', ' ', $name)), 0, 255);
        if ($name === '') {
            throw ProductionException::invalid('Укажите наименование вида работ');
        }

        return $name;
    }

    private function unit(string $unit): string
    {
        $unit = trim(preg_replace('/\s+/u', ' ', $unit));
        if ($unit === '') {
            throw ProductionException::invalid('Укажите единицу измерения');
        }
        if (mb_strlen($unit) > 20) {
            throw ProductionException::invalid('Единица измерения длиннее 20 знаков');
        }

        return $unit;
    }

    /** 1, 21, 31… — «объекта»; остальные — «объектов» (в родительном падеже) */
    private function oneObject(int $n): bool
    {
        return $n % 10 === 1 && $n % 100 !== 11;
    }
}
