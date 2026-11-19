<?php

namespace App\Production\Tasks;

use App\Models\TimesheetContract;
use App\Production\Erp\ErpGateway;
use App\Production\Objects\ObjectDirectory;
use Illuminate\Support\Collection;

/** Варианты фильтров календаря (п. 6.4): объект, бригада или сотрудник-исполнитель, создатель, техника, водитель */
final class TaskFilterOptions
{
    public function __construct(private readonly ObjectDirectory $objects, private readonly ErpGateway $erp)
    {
    }

    public function for(Viewer $viewer): array
    {
        // select() заменяет `timesheet_contracts.*` из TaskVisibility: DISTINCT — по одной колонке, а не по строкам заданий
        $ids = fn (string $column, ?callable $where = null) => (new TaskVisibility())->query($viewer)
            ->when($where, $where)
            ->whereNotNull($column)
            ->select($column)
            ->distinct()
            ->pluck($column)
            ->map(fn ($id) => (int) $id)
            ->all();

        $objects = $this->objects->findMany($ids('timesheet_contracts.contract_id'))
            ->map(fn (array $o) => ['id' => $o['id'], 'number' => $o['number'], 'title' => $o['title']])
            ->sortBy(fn ($o) => [strlen((string) $o['number']), $o['number']])
            ->values();

        $brigades = collect($this->erp->brigades($ids('t.brigade_id')))
            ->map(fn (array $b) => ['id' => $b['id'], 'name' => $b['name']])
            ->sortBy('name')
            ->values();

        // Техника — по снимку в задании: машину могли переименовать или убрать из ERP (П-ОБ-23б)
        $latest = (new TaskVisibility())->query($viewer)
            ->whereNotNull('timesheet_contracts.vehicle_id')
            ->select('timesheet_contracts.vehicle_id')
            ->selectRaw('MAX(timesheet_contracts.id) as last_id')
            ->groupBy('timesheet_contracts.vehicle_id')
            ->toBase()
            ->pluck('last_id');
        $vehicles = TimesheetContract::query()
            ->whereIn('id', $latest)
            ->get(['vehicle_id', 'vehicle_name', 'vehicle_plate'])
            ->map(fn ($row) => ['id' => (int) $row->vehicle_id, 'name' => $row->vehicle_name, 'plate' => $row->vehicle_plate])
            ->sortBy('name')
            ->values();

        return [
            'objects' => $objects,
            'brigades' => $brigades,
            'executors' => $this->people($ids('t.staff_id', fn ($q) => $q->whereNull('t.brigade_id'))),
            'creators' => $this->people($ids('timesheet_contracts.creator_staff_id')),
            'vehicles' => $vehicles,
            'drivers' => $this->people($ids('timesheet_contracts.driver_staff_id')),
        ];
    }

    private function people(array $ids): Collection
    {
        return collect($this->erp->people($ids))
            ->map(fn (array $p) => ['id' => $p['id'], 'name' => $p['name']])
            ->sortBy('name')
            ->values();
    }
}
