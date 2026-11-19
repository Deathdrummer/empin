<?php

namespace App\Production\Erp;

use App\Models\AdminUser;
use App\Models\Contract;
use App\Models\Erp\Brigade;
use App\Models\ContractReadinessEvent;
use App\Models\Erp\ContractPlanItem;
use App\Models\Erp\ContractPlanRevision;
use App\Models\Erp\ContractProductionInfo;
use App\Models\Erp\WorkType;
use App\Models\Staff;
use App\Production\Reports\ObjectReadiness;
use App\Production\Reports\ReportAllocations;
use App\Production\Support\ProductionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Производственные сведения объекта в ERP («Доработки ERP», пп. 1 и 5; ТЗ П-ОБ-07, П-ОБ-08, П-ОБ-30…32, п. 10.3.7)
 */
class ObjectProductionRegistry
{
    public const ENTITY = 'contract';

    /** Сведения для окна «Производство» */
    public function info(Contract $contract): array
    {
        $info = ContractProductionInfo::find($contract->id);
        $brigadeIds = DB::table('contract_brigades')->where('contract_id', $contract->id)->pluck('brigade_id')->map(fn ($id) => (int) $id)->all();

        return [
            'responsible_staff_id' => $info?->responsible_staff_id ? (int) $info->responsible_staff_id : null,
            'brigade_ids' => $brigadeIds,
            'readiness_percent' => $info?->readiness_percent !== null ? (float) $info->readiness_percent : null,
            'readiness_changed_at' => $info?->readiness_changed_at,
        ];
    }

    /**
     * Сохранить ответственного, закреплённые бригады и готовность
     *
     * @param int[] $brigadeIds
     *
     */
    public function saveInfo(Contract $contract, ?int $responsibleId, array $brigadeIds, mixed $readiness = null, mixed $readinessShown = null): void
    {
        $brigadeIds = array_values(array_unique(array_map('intval', $brigadeIds)));
        $before = $this->info($contract);
        $added = array_diff($brigadeIds, $before['brigade_ids']);
        $removed = array_diff($before['brigade_ids'], $brigadeIds);
        $shown = $this->percent($readinessShown);
        $newReadiness = $this->percent($readiness, validate: true, current: $shown);
        if ($newReadiness !== null && $newReadiness === $shown) {
            $newReadiness = null;
        }
        $productionChanged = $responsibleId !== $before['responsible_staff_id'] || $added || $removed;
        if (!$productionChanged && $newReadiness === null) {
            return;
        }
        if ($responsibleId && $responsibleId !== $before['responsible_staff_id'] && !Staff::whereKey($responsibleId)->where('working', 1)->exists()) {
            throw ProductionException::invalid('Ответственный не найден или не работает');
        }
        if ($added && Brigade::whereIn('id', $added)->whereNull('disbanded_on')->count() !== count($added)) {
            throw ProductionException::invalid('Закрепить можно только действующую бригаду');
        }

        DB::transaction(function () use ($contract, $responsibleId, $added, $removed, $before, $brigadeIds, $productionChanged, $newReadiness, $shown) {
            if ($newReadiness !== null) {
                $this->changeReadiness($contract, $newReadiness, $shown);
            }
            if (!$productionChanged) {
                return;
            }
            if ($responsibleId !== $before['responsible_staff_id']) {
                ContractProductionInfo::updateOrCreate(['contract_id' => $contract->id], ['responsible_staff_id' => $responsibleId]);
            }
            if ($removed) {
                DB::table('contract_brigades')->where('contract_id', $contract->id)->whereIn('brigade_id', $removed)->delete();
            }
            foreach ($added as $brigadeId) {
                DB::table('contract_brigades')->insert(['contract_id' => $contract->id, 'brigade_id' => $brigadeId, 'created_at' => now(), 'updated_at' => now()]);
            }
            ErpAudit::write(self::ENTITY, $contract->id, 'production_changed',
                ['responsible' => ErpAudit::person($before['responsible_staff_id']), 'brigades' => $this->brigadeNames($before['brigade_ids'])],
                ['responsible' => ErpAudit::person($responsibleId), 'brigades' => $this->brigadeNames($brigadeIds)]);
        });
    }

    /**
     * Изменение готовности в ERP: новое актуальное значение (сразу действует в приложении: подсказки объектов и др.)
     * и запись в истории готовности
     */
    private function changeReadiness(Contract $contract, float $percent, ?float $shown): void
    {
        app(ObjectReadiness::class)->apply($contract->id, $percent, ContractReadinessEvent::SOURCE_ERP,
            ['admin_id' => Auth::guard('admin')->id()], checkShown: true, shown: $shown);
    }

    /**
     * История готовности объекта для окна: новые сверху, кто (администратор ERP или сотрудник) и когда
     *
     * @return array<int, array{at: mixed, before: ?float, after: ?float, who: ?string, source: string, reason: ?string}>
     */
    public function readinessHistory(Contract $contract): array
    {
        $events = ContractReadinessEvent::where('contract_id', $contract->id)->orderByDesc('id')->get();
        $admins = AdminUser::whereIn('id', $events->pluck('admin_id')->filter()->unique())->get()->keyBy('id');

        return $events->map(fn (ContractReadinessEvent $e) => [
            'at' => $e->created_at,
            'before' => $e->percent_before !== null ? (float) $e->percent_before : null,
            'after' => $e->percent_after !== null ? (float) $e->percent_after : null,
            'who' => $e->admin_id ? ($admins[$e->admin_id]?->name ?: $admins[$e->admin_id]?->pseudoname) : (ErpAudit::person($e->staff_id)['name'] ?? null),
            'source' => ContractReadinessEvent::sourceTitle($e->source),
            'reason' => $e->reason,
        ])->all();
    }

    /** Процент готовности 0–100, до двух знаков */
    private function percent(mixed $value, bool $validate = false, ?float $current = null): ?float
    {
        $text = str_replace([' ', ',', '%'], ['', '.', ''], trim((string) $value));
        if ($text === '') {
            if ($validate && $current !== null) {
                throw ProductionException::invalid('Готовность — число от 0 до 100');
            }

            return null;
        }
        if (!is_numeric($text) || (float) $text < 0 || (float) $text > 100) {
            if ($validate) {
                throw ProductionException::invalid('Готовность — число от 0 до 100');
            }

            return null;
        }

        return round((float) $text, 2);
    }

    public function percentText(?float $percent): string
    {
        return $percent === null ? '-' : rtrim(rtrim(number_format($percent, 2, ',', ' '), '0'), ',').' %';
    }

    /**
     * Сохранить план: новая редакция действует сразу (П-ОБ-32)
     *
     * @param array<int, array{work_type_id: int|string, quantity: float|string}> $items
     *
     */
    public function revisePlan(Contract $contract, array $items): ?ContractPlanRevision
    {
        $current = ContractPlanRevision::where('contract_id', $contract->id)->orderByDesc('id')->first();
        $currentItems = $current ? $current->items()->get()->mapWithKeys(fn (ContractPlanItem $i) => [(int) $i->work_type_id => $this->quantity($i->quantity)])->all() : [];

        $next = [];
        foreach ($items as $item) {
            $typeId = (int) ($item['work_type_id'] ?? 0);
            if (!$typeId) {
                throw ProductionException::invalid('Выберите вид работ в каждой строке плана');
            }
            if (isset($next[$typeId])) {
                throw ProductionException::invalid('Вид работ указан в плане дважды');
            }
            $next[$typeId] = $this->quantity($item['quantity'] ?? null, true);
        }
        $types = WorkType::withTrashed()->whereIn('id', array_keys($next))->get()->keyBy('id');
        foreach (array_keys($next) as $typeId) {
            $type = $types[$typeId] ?? null;
            if (!$type || ($type->trashed() && !array_key_exists($typeId, $currentItems))) {
                throw ProductionException::invalid('Вид работ удалён из справочника');
            }
        }
        ksort($next);
        ksort($currentItems);
        if ($next === $currentItems) {
            return null;
        }

        return DB::transaction(function () use ($contract, $current, $next, $types) {
            $revision = ContractPlanRevision::create(['contract_id' => $contract->id, 'created_by_staff_id' => null]);
            foreach ($next as $typeId => $quantity) {
                ContractPlanItem::create(['revision_id' => $revision->id, 'work_type_id' => $typeId, 'quantity' => $quantity]);
            }
            // Отнесение к допам — по новой редакции; сами факты не меняются (П-ФК-24, П-ОБ-32)
            app(ReportAllocations::class)->recompute($contract->id);
            ErpAudit::write(self::ENTITY, $contract->id, 'plan_revised', $current ? ['revision_id' => $current->id] : null, [
                'revision_id' => $revision->id,
                'items' => collect($next)->map(fn ($q, $typeId) => ['work_type_id' => $typeId, 'name' => $types[$typeId]->name, 'unit' => $types[$typeId]->unit, 'quantity' => $q])->values()->all(),
            ]);

            return $revision;
        });
    }

    /**
     * Редакции плана объекта для окна: новые сверху, с позициями и тем, кто сохранил
     *
     * @return array<int, array{id: int, at: mixed, admin: ?string, items: array}>
     */
    public function revisions(Contract $contract): array
    {
        $who = collect(ErpAudit::history(self::ENTITY, $contract->id))
            ->filter(fn ($e) => $e['action'] === 'plan_revised')
            ->mapWithKeys(fn ($e) => [(int) ($e['after']['revision_id'] ?? 0) => $e['admin']]);

        return ContractPlanRevision::where('contract_id', $contract->id)->orderByDesc('id')
            ->with(['items.workType'])
            ->get()
            ->map(fn (ContractPlanRevision $r) => [
                'id' => $r->id,
                'at' => $r->created_at,
                'admin' => $who[$r->id] ?? null,
                'items' => $r->items->map(fn (ContractPlanItem $i) => [
                    'work_type_id' => (int) $i->work_type_id,
                    'name' => $i->workType?->name,
                    'unit' => $i->workType?->unit,
                    'deleted' => (bool) $i->workType?->trashed(),
                    'quantity' => (float) $i->quantity,
                ])->sortBy('name')->values()->all(),
            ])->all();
    }

    /** @param int[] $ids */
    private function brigadeNames(array $ids): array
    {
        return Brigade::whereIn('id', $ids)->orderBy('name')->get(['id', 'name'])->map(fn (Brigade $b) => ['id' => $b->id, 'name' => $b->name])->all();
    }

    /** Плановое количество: больше нуля, до трёх знаков после запятой (как в базе) */
    private function quantity(mixed $value, bool $validate = false): string
    {
        $text = str_replace([' ', ','], ['', '.'], trim((string) $value));
        if ($validate && (!is_numeric($text) || (float) $text <= 0 || (float) $text >= 1e11)) {
            throw ProductionException::invalid('Плановое количество — число больше нуля');
        }

        return number_format(round((float) $text, 3), 3, '.', '');
    }
}
