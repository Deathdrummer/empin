<?php

namespace App\Production\Objects;

use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Объекты ERP для выбора в задании (ТЗ, п. 5.3 «Поиск объекта», П-ЗД-05…08) */
final class ObjectDirectory
{
    public const GROUP_RECENT = 'recent';
    public const GROUP_MINE = 'mine';

    private const SEARCH_LIMIT = 50;
    private const LIST_LIMIT = 200;

    /** Активный объект (не в архиве) — для одного значения `contracts.archive` */
    public static function isActive(mixed $archive): bool
    {
        return (int) $archive !== 1;
    }

    /** Условие «активный объект» в SQL; так же ERP отбирает «все активные» (`whereNot('archive', 1)`) */
    public static function whereActive(Builder $query, string $column = 'c.archive'): Builder
    {
        return $query->where(fn ($q) => $q->whereNull($column)->orWhere($column, '<>', 1));
    }

    /**
     * Подсказки без поиска (П-ЗД-06, ИС-ОБ-02): сначала объекты, на которые сотрудник недавно ставил задания, новые
     * сверху; затем остальные, где он ответственный, по номеру ERP
     */
    public function suggestions(int $staffId, CarbonInterface $today): Collection
    {
        $recentIds = $this->recentIds($staffId, $today, excludeComplete: true);
        $recent = $this->rowsInOrder($recentIds)->map(fn ($row) => $this->item($row, $staffId, self::GROUP_RECENT));

        $mine = $this->base()
            ->where('pi.responsible_staff_id', $staffId)
            ->whereNotIn('c.id', $recentIds ?: [0])
            ->where(fn ($q) => $q->whereNull('pi.readiness_percent')->orWhere('pi.readiness_percent', '<', 100))
            ->orderByRaw('LENGTH(c.object_number), c.object_number')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(fn ($row) => $this->item($row, $staffId, self::GROUP_MINE));

        return $recent->concat($mine)->values();
    }

    /**
     * Фильтр по бригаде (П-ЗД-07, ИС-ОБ-04): все активные объекты, за которыми закреплена бригада, кто бы ни был
     * ответственным, в том числе с готовностью 100 % (раздел 12, п. 31)
     */
    public function forBrigade(int $brigadeId, int $staffId, CarbonInterface $today, ?string $search = null): Collection
    {
        $query = $this->base()
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('contract_brigades as cb')
                ->whereColumn('cb.contract_id', 'c.id')
                ->where('cb.brigade_id', $brigadeId));
        if ($search !== null) {
            $this->applySearch($query, $search);
        }
        $rows = $query->orderByRaw('LENGTH(c.object_number), c.object_number')->limit(self::LIST_LIMIT)->get();

        $recentOrder = array_flip($this->recentIds($staffId, $today, excludeComplete: false));

        return $rows
            ->sortBy(fn ($row) => isset($recentOrder[$row->id]) ? [0, $recentOrder[$row->id]] : [1, 0], SORT_REGULAR)
            ->map(fn ($row) => $this->item($row, $staffId, isset($recentOrder[$row->id]) ? self::GROUP_RECENT : null))
            ->values();
    }

    /**
     * Поиск (П-ОБ-12, ИС-ЗД-02): по всем активным объектам независимо от готовности и закрепления; архив не находится
     */
    public function search(string $search, int $staffId): Collection
    {
        $query = $this->applySearch($this->base(), $search)
            ->orderByRaw('c.object_number = ? DESC', [trim($search)])
            ->orderByRaw('LENGTH(c.object_number), c.object_number')
            ->limit(self::SEARCH_LIMIT);

        return $query->get()->map(fn ($row) => $this->item($row, $staffId, null))->values();
    }

    /** Один объект (активный или архивный) — для карточки задания и проверок */
    public function find(int $contractId): ?object
    {
        return $this->base(activeOnly: false)->where('c.id', $contractId)->first();
    }

    /**
     * Объекты заданий (активные и архивные — прежние задания по архивному объекту остаются, п. 10.3)
     *
     * @param int[] $ids
     * @return Collection<int, object> по id
     */
    public function findMany(array $ids): Collection
    {
        return $ids ? $this->base(activeOnly: false)->whereIn('c.id', $ids)->get()->keyBy('id') : collect();
    }

    /** Сведения об объекте для приложения: номер, название, расположение, ответственный, утверждённая готовность (п. 5.4) */
    public function item(object $row, int $staffId, ?string $group): array
    {
        $responsibleId = $row->responsible_staff_id ? (int) $row->responsible_staff_id : null;

        return [
            'id' => (int) $row->id,
            'number' => $row->object_number,
            'title' => $row->title,
            'titul' => $row->titul,
            'locality' => $row->locality,
            'active' => self::isActive($row->archive),
            'responsible' => $responsibleId ? ['id' => $responsibleId, 'name' => self::name($row->r_sname, $row->r_fname, $row->r_mname)] : null,
            'readiness' => $row->readiness_percent !== null ? (float) $row->readiness_percent : null,
            // Свой — где сотрудник ответственный; чужой или без ответственного — предупреждение, но не запрет (П-ЗД-08)
            'own' => $responsibleId === $staffId,
            'group' => $group,
        ];
    }

    public static function name(?string $sname, ?string $fname, ?string $mname): string
    {
        return trim(preg_replace('/\s+/u', ' ', "{$sname} {$fname} {$mname}"));
    }

    private function base(bool $activeOnly = true): Builder
    {
        $query = DB::table('contracts as c')
            ->leftJoin('contract_production_info as pi', 'pi.contract_id', '=', 'c.id')
            ->leftJoin('staff as rs', 'rs.id', '=', 'pi.responsible_staff_id')
            ->select([
                'c.id', 'c.object_number', 'c.title', 'c.titul', 'c.locality', 'c.archive',
                'pi.responsible_staff_id', 'pi.readiness_percent',
                'rs.sname as r_sname', 'rs.fname as r_fname', 'rs.mname as r_mname',
            ]);

        return $activeOnly ? self::whereActive($query) : $query;
    }

    private function applySearch(Builder $query, string $search): Builder
    {
        $like = '%'.addcslashes(trim($search), '\\%_').'%';

        return $query->where(fn ($q) => $q->where('c.object_number', 'like', $like)
            ->orWhere('c.title', 'like', $like)
            ->orWhere('c.titul', 'like', $like)
            ->orWhere('c.locality', 'like', $like));
    }

    /**
     * Объекты, на которые сотрудник ставил задания (он — создатель), за последние N дней по дате задания, от
     * последнего задания к более ранним (интервью, FR-139)
     *
     * @return int[]
     *
     */
    private function recentIds(int $staffId, CarbonInterface $today, bool $excludeComplete): array
    {
        $query = DB::table('timesheet_contracts as tc')
            ->join('timesheet_teams as t', 't.id', '=', 'tc.team_id')
            ->join('contracts as c', 'c.id', '=', 'tc.contract_id')
            ->leftJoin('contract_production_info as pi', 'pi.contract_id', '=', 'c.id')
            ->where('tc.creator_staff_id', $staffId)
            ->where('t.day', '>=', $today->subDays((int) config('production.recent_objects_days', 60))->toDateString());
        self::whereActive($query);
        if ($excludeComplete) {
            $query->where(fn ($q) => $q->whereNull('pi.readiness_percent')->orWhere('pi.readiness_percent', '<', 100));
        }

        return $query->groupBy('c.id')
            ->orderByRaw('MAX(t.day) DESC, MAX(tc.id) DESC')
            ->limit(self::LIST_LIMIT)
            ->pluck('c.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @param int[] $ids */
    private function rowsInOrder(array $ids): Collection
    {
        if (!$ids) {
            return collect();
        }
        $rows = $this->base()->whereIn('c.id', $ids)->get()->keyBy('id');

        return collect($ids)->map(fn ($id) => $rows->get($id))->filter()->values();
    }
}
