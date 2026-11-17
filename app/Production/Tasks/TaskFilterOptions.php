<?php

namespace App\Production\Tasks;

use App\Models\Erp\Brigade;
use App\Models\Staff;
use App\Models\TimesheetContract;
use App\Production\Objects\ObjectDirectory;
use Illuminate\Support\Collection;

/**
 * Варианты фильтров календаря (п. 6.4): объект, бригада или сотрудник-исполнитель, создатель, техника, водитель.
 * Берутся только из заданий, которые сотрудник видит (TaskVisibility), поэтому фильтр ничего не раскрывает сверх
 * календаря и работает у любого, кто видит задания, в том числе у руководства с правом 6 без прав на справочники.
 */
final class TaskFilterOptions
{
    public function __construct(private readonly ObjectDirectory $objects)
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
            ->map(fn ($row) => ['id' => (int) $row->id, 'number' => $row->object_number, 'title' => $row->title])
            ->sortBy(fn ($o) => [strlen((string) $o['number']), $o['number']])
            ->values();

        $brigades = Brigade::query()->whereIn('id', $ids('t.brigade_id'))->orderBy('name')->get(['id', 'name'])
            ->map(fn (Brigade $b) => ['id' => $b->id, 'name' => $b->name]);

        // Техника — по снимку в задании: машину могли переименовать или убрать из ERP (П-ОБ-23б). Название берётся
        // из последнего задания с этой машиной; база отдаёт по одной строке на машину, а не все задания с техникой
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
        return Staff::query()->whereIn('id', $ids)->get(['id', 'sname', 'fname', 'mname'])
            ->map(fn (Staff $s) => ['id' => (int) $s->id, 'name' => ObjectDirectory::name($s->sname, $s->fname, $s->mname)])
            ->sortBy('name')
            ->values();
    }
}
