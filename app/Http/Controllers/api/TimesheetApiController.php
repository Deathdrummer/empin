<?php namespace App\Http\Controllers\api;

use App\Helpers\DdrDateTime;
use App\Http\Controllers\Controller;
use App\Http\Filters\ContractFilter;
use App\Http\Resources\TimesheetChatResource;
use App\Http\Resources\TimesheetTeamResource;
use App\Models\Contract as ContractModel;
use App\Models\Department;
use App\Models\Staff;
use App\Models\TimesheetChat;
use App\Models\TimesheetContract;
use App\Models\TimesheetTeam;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TimesheetApiController extends Controller {

    /**
     * Получить данные слайдов по дням
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSlidesData(Request $request) {
        [
            'indexes' => $indexes,
        ] = $request->validate([
            'indexes' => 'required|array',
            'indexes.*' => 'integer',
        ]);

        $indexes = array_map('intval', $indexes);

        $teams = TimesheetTeam::getByDaysIndexes($indexes)
            ->with('profile')
            ->with('contracts.contract')
            ->with('contracts.chat.profile.registred')
            ->get()
            ->groupBy(fn($team) => $team->day instanceof Carbon ? $team->day->toDateString() : $team->day);

        $teams = $teams->map(fn($group) => TimesheetTeamResource::collection($group)->resolve());

        $daysData = [];
        foreach ($indexes as $idx) {
            $dateObj = DdrDateTime::getOffsetDate($idx);
            $day = $dateObj->toDateString();
            $weekDayNum = (int)DdrDateTime::numOfWeek($dateObj);

            $daysData[] = [
                'index' => (int)$idx,
                'weekDay' => DdrDateTime::dayOfWeek($dateObj),
                'humanDate' => DdrDateTime::dateToHuman($dateObj, 'ru'),
                'day' => $day,
                'isWeekEnd' => in_array($weekDayNum, [6,7]),
                'isToday' => $idx == 0,
                'teams' => $teams[$day] ?? null,
            ];
        }

        return response()->json($daysData);
    }

    /**
     * Получить данные для одного дня
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSlideData(Request $request) {
        [
            'index' => $index,
        ] = $request->validate([
            'index' => 'required|integer',
        ]);

        $index = (int)$index;

        $dateObj = DdrDateTime::getOffsetDate($index);
        $day = $dateObj->toDateString();
        $weekDayNum = (int)DdrDateTime::numOfWeek($dateObj);

        $teams = TimesheetTeam::getByDaysIndexes([$index])
            ->with('profile')
            ->with('contracts.contract')
            ->with('contracts.chat.profile.registred')
            ->get();

        $teamsData = TimesheetTeamResource::collection($teams)->resolve();

        $dayData = [
            'index' => $index,
            'weekDay' => DdrDateTime::dayOfWeek($dateObj),
            'humanDate' => DdrDateTime::dateToHuman($dateObj, 'ru'),
            'day' => $day,
            'isWeekEnd' => in_array($weekDayNum, [6,7]),
            'isToday' => $index == 0,
            'teams' => $teamsData,
        ];

        return response()->json($dayData);
    }

    /**
     * Поиск контрактов
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function contractsList(Request $request) {
        [
            'search' => $search,
        ] = $request->validate([
            'search' => 'required|string',
        ]);

        // Простой поиск без фильтра (чтобы избежать зависимости от UserService)
        $data = ContractModel::where(function($query) use ($search) {
                $query->where('object_number', 'like', '%'.$search.'%')
                    ->orWhere('title', 'like', '%'.$search.'%')
                    ->orWhere('titul', 'like', '%'.$search.'%');
            })
            ->select(['id', 'object_number', 'title', 'titul'])
            ->limit(50)
            ->get();

        return response()->json($data);
    }

    /**
     * Добавить сотрудника в команду на день
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addTeam(Request $request) {
        // Проверка прав
        if (!$request->user()->can('mobile-app-can-create-team:site')) {
            return response()->json([
                'error' => 'У вас нет прав для добавления бригады'
            ], 403);
        }

        $validFields = $request->validate([
            'staff_id' => 'required|integer',
            'day' => 'required|date',
        ]);

        $res = TimesheetTeam::create($validFields);

        return response()->json(['id' => $res->id]);
    }

    /**
     * Удалить сотрудника из команды
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeTeam($id) {
        $teamRecord = TimesheetTeam::find($id);

        if (!$teamRecord) {
            return response()->json(['success' => false], 404);
        }

        $teamRecord->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Добавить контракт к команде
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addContract(Request $request) {
        [
            'contract_id' => $contractId,
            'team_id' => $teamId,
        ] = $request->validate([
            'contract_id' => 'required|integer',
            'team_id' => 'required|integer',
        ]);

        $team = TimesheetTeam::find($teamId);

        if (!$team) {
            return response()->json(['error' => 'Team not found'], 404);
        }

        $contract = $team->contracts()->create(['contract_id' => $contractId]);

        return response()->json(['id' => $contract->id]);
    }

    /**
     * Удалить контракт из команды
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeContract($id) {
        $timesheetContract = TimesheetContract::find($id);

        if (!$timesheetContract) {
            return response()->json(['success' => false], 404);
        }

        $timesheetContract->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Добавить комментарий к контракту
     *
     * @param Request $request
     * @return TimesheetChatResource
     */
    public function addComment(Request $request) {
        [
            'timesheet_contract_id' => $timesheetContractId,
            'message' => $message,
        ] = $request->validate([
            'timesheet_contract_id' => 'required|integer',
            'message' => 'required|string',
        ]);

        $contract = TimesheetContract::find($timesheetContractId);

        if (!$contract) {
            return response()->json(['error' => 'Contract not found'], 404);
        }

        $comment = $contract->chat()->create([
            'from_id' => $request->user()->staff_id,
            'message' => $message,
        ]);

        $comment->load('profile.registred');

        return new TimesheetChatResource($comment);
    }

    /**
     * Удалить комментарий
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeComment($id) {
        $timesheetMess = TimesheetChat::find($id);

        if (!$timesheetMess) {
            return response()->json(['success' => false], 404);
        }

        $timesheetMess->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Получить список сотрудников
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStaff() {
        $depsIds = Department::where('show_in_timesheet', true)->select('id')->pluck('id');
        $staff = Staff::select(['id', 'sname', 'fname', 'mname'])
            ->whereRelation('registred', fn($q) => $q->whereIn('department_id', $depsIds))
            ->get();

        return response()->json($staff);
    }
}
