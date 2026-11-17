<?php

namespace App\Production\Tasks;

use App\Models\TimesheetContract;
use App\Production\Rights\Grant;
use App\Production\Rights\Right;
use Illuminate\Database\Eloquent\Builder;

/**
 * Запрос заданий, которые сотрудник видит, — те же основания, что в TaskAccess::bases (П-ЗД-20), но в SQL,
 * чтобы календарь не перебирал все задания компании. Задание, доступное по нескольким основаниям, — одна строка (П-ЗД-20).
 */
final class TaskVisibility
{
    /** @return Builder<TimesheetContract> задания с присоединённым исполнителем на дату (`t`) */
    public function query(Viewer $viewer): Builder
    {
        $me = $viewer->staffId;
        $viewAll = $viewer->rights->grantsOf(Right::TaskViewAll);
        $seesEverything = $viewAll->contains(fn (Grant $g) => $g->scopeAll);
        $viewCreators = $viewAll->flatMap(fn (Grant $g) => $g->staffIds)->unique()->values()->all();
        $viewBrigades = $viewAll->flatMap(fn (Grant $g) => $g->brigadeIds)->unique()->values()->all();

        $query = TimesheetContract::query()
            ->select('timesheet_contracts.*')
            ->join('timesheet_teams as t', 't.id', '=', 'timesheet_contracts.team_id');

        if ($seesEverything) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($me, $viewer, $viewCreators, $viewBrigades) {
            $q->where('timesheet_contracts.creator_staff_id', $me)
                ->orWhere(fn ($w) => $w->whereNull('t.brigade_id')->where('t.staff_id', $me))
                ->orWhere('timesheet_contracts.driver_staff_id', $me)
                ->orWhereExists(fn ($e) => $e->selectRaw('1')->from('timesheet_contract_invites as i')
                    ->whereColumn('i.timesheet_contract_id', 'timesheet_contracts.id')
                    ->where('i.staff_id', $me)
                    ->whereNull('i.revoked_at'));
            if ($viewer->brigadeIds) {
                $q->orWhereIn('t.brigade_id', $viewer->brigadeIds);
            }
            if ($viewCreators) {
                $q->orWhereIn('timesheet_contracts.creator_staff_id', $viewCreators);
            }
            if ($viewBrigades) {
                $q->orWhereIn('t.brigade_id', $viewBrigades);
            }
        });
    }
}
