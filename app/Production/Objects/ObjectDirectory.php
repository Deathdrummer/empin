<?php

namespace App\Production\Objects;

use App\Production\Erp\ErpGateway;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Объекты ERP для выбора в задании (ТЗ, п. 5.3 «Поиск объекта», П-ЗД-05…08). Данные объектов — из шлюза ERP;
 * «недавние объекты» — из заданий приложения
 */
final class ObjectDirectory
{
    public const GROUP_RECENT = 'recent';
    public const GROUP_MINE = 'mine';

    private const SEARCH_LIMIT = 50;
    private const LIST_LIMIT = 200;

    public function __construct(private readonly ErpGateway $erp)
    {
    }

    /**
     * Подсказки без поиска (П-ЗД-06, ИС-ОБ-02): сначала объекты, на которые сотрудник недавно ставил задания, новые
     * сверху; затем остальные, где он ответственный, по номеру ERP
     */
    public function suggestions(int $staffId, CarbonInterface $today): Collection
    {
        $recentIds = array_slice($this->erp->activeObjectIds($this->recentIds($staffId, $today), incompleteOnly: true), 0, self::LIST_LIMIT);
        $mineIds = $this->erp->objectsOfResponsible($staffId, $recentIds, incompleteOnly: true, limit: self::LIST_LIMIT);

        return $this->listed($recentIds, $staffId, self::GROUP_RECENT)->concat($this->listed($mineIds, $staffId, self::GROUP_MINE))->values();
    }

    /**
     * Фильтр по бригаде (П-ЗД-07, ИС-ОБ-04): все активные объекты, за которыми закреплена бригада, кто бы ни был
     * ответственным, в том числе с готовностью 100 % (раздел 12, п. 31)
     */
    public function forBrigade(int $brigadeId, int $staffId, CarbonInterface $today, ?string $search = null): Collection
    {
        $ids = $this->erp->objectsOfBrigade($brigadeId, $search, self::LIST_LIMIT);
        $recentOrder = array_flip($this->recentIds($staffId, $today));
        // Недавние — сверху в порядке недавности, остальные — по номеру ERP
        $position = array_flip($ids);
        usort($ids, fn ($a, $b) => [isset($recentOrder[$a]) ? 0 : 1, $recentOrder[$a] ?? $position[$a]] <=> [isset($recentOrder[$b]) ? 0 : 1, $recentOrder[$b] ?? $position[$b]]);
        $presented = $this->present($this->erp->objects($ids), $staffId);

        return collect($ids)->filter(fn ($id) => isset($presented[$id]))
            ->map(fn ($id) => ['group' => isset($recentOrder[$id]) ? self::GROUP_RECENT : null] + $presented[$id])
            ->values();
    }

    /**
     * Поиск (П-ОБ-12, ИС-ЗД-02): по всем активным объектам независимо от готовности и закрепления; архив не находится
     */
    public function search(string $search, int $staffId): Collection
    {
        return $this->listed($this->erp->searchObjects($search, self::SEARCH_LIMIT), $staffId, null);
    }

    /** Один объект (активный или архивный) — для карточки задания и проверок */
    public function find(int $objectId): ?array
    {
        return $this->erp->objects([$objectId])[$objectId] ?? null;
    }

    /**
     * Объекты заданий (активные и архивные — прежние задания по архивному объекту остаются, п. 10.3)
     *
     * @param int[] $ids
     * @return Collection<int, array> по id
     */
    public function findMany(array $ids): Collection
    {
        return collect($this->erp->objects($ids));
    }

    /**
     * Сведения об объектах для приложения: номер, название, расположение, ответственный, утверждённая готовность
     * (п. 5.4); ответственные — одним запросом
     *
     * @param array<int, array> $objects объекты шлюза ERP
     * @return array<int, array> по id
     */
    public function present(array|Collection $objects, int $staffId): array
    {
        $objects = collect($objects);
        $people = $this->erp->people($objects->pluck('responsible_staff_id')->filter()->unique()->values()->all());

        return $objects->mapWithKeys(fn (array $object) => [$object['id'] => [
            'id' => $object['id'],
            'number' => $object['number'],
            'title' => $object['title'],
            'titul' => $object['titul'],
            'locality' => $object['locality'],
            'active' => $object['active'],
            'responsible' => $object['responsible_staff_id']
                ? ['id' => $object['responsible_staff_id'], 'name' => $people[$object['responsible_staff_id']]['name'] ?? null]
                : null,
            'readiness' => $object['readiness'],
            // Свой — где сотрудник ответственный; чужой или без ответственного — предупреждение, но не запрет (П-ЗД-08)
            'own' => $object['responsible_staff_id'] === $staffId,
            'group' => null,
        ]])->all();
    }

    public static function name(?string $sname, ?string $fname, ?string $mname): string
    {
        return trim(preg_replace('/\s+/u', ' ', "{$sname} {$fname} {$mname}"));
    }

    /** @param int[] $ids */
    private function listed(array $ids, int $staffId, ?string $group): Collection
    {
        $presented = $this->present($this->erp->objects($ids), $staffId);

        return collect($ids)->filter(fn ($id) => isset($presented[$id]))->map(fn ($id) => ['group' => $group] + $presented[$id])->values();
    }

    /**
     * Объекты, на которые сотрудник ставил задания (он — создатель), за последние N дней по дате задания, от
     * последнего задания к более ранним (интервью, FR-139). Активность и готовность проверяет ERP.
     *
     * @return int[]
     */
    private function recentIds(int $staffId, CarbonInterface $today): array
    {
        return DB::table('timesheet_contracts as tc')
            ->join('timesheet_teams as t', 't.id', '=', 'tc.team_id')
            ->where('tc.creator_staff_id', $staffId)
            ->where('t.day', '>=', $today->subDays((int) config('production.recent_objects_days', 60))->toDateString())
            ->groupBy('tc.contract_id')
            ->orderByRaw('MAX(t.day) DESC, MAX(tc.id) DESC')
            ->limit(2000)
            ->pluck('tc.contract_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
