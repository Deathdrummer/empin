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
        $validated = $request->validate([
            'indexes' => 'required|array',
            'indexes.*' => 'integer',
            'filters' => 'nullable|array',
            'filters.teams' => 'nullable|array',
            'filters.teams.*' => 'integer',
            'filters.contracts' => 'nullable|array',
            'filters.contracts.*' => 'integer',
        ]);

        $indexes = array_map('intval', $validated['indexes']);
        $filters = $validated['filters'] ?? null;

        // Проверяем наличие активных фильтров
        $hasTeamsFilter = !empty($filters['teams']) && is_array($filters['teams']);
        $hasContractsFilter = !empty($filters['contracts']) && is_array($filters['contracts']);
        $hasActiveFilters = $hasTeamsFilter || $hasContractsFilter;

        // Логируем для отладки
        \Log::info('=== getSlidesData ===', [
            'indexes_count' => count($indexes),
            'filters' => $filters,
            'hasActiveFilters' => $hasActiveFilters,
        ]);

        $query = TimesheetTeam::getByDaysIndexes($indexes);

        // Применяем фильтр по командам (мастерам)
        if ($hasTeamsFilter) {
            $query->whereIn('staff_id', $filters['teams']);
        }

        // Применяем фильтр по контрактам
        if ($hasContractsFilter) {
            $query->whereHas('contracts', function($q) use ($filters) {
                $q->whereIn('contract_id', $filters['contracts']);
            });
        }

        $teams = $query
            ->with('profile')
            ->with(['contracts' => function($q) use ($hasContractsFilter, $filters) {
                // Фильтруем контракты если указан фильтр
                if ($hasContractsFilter) {
                    $q->whereIn('contract_id', $filters['contracts']);
                }
            }])
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

            // ИСПРАВЛЕНО: Возвращаем все дни диапазона, даже если нет совпадений по фильтру
            // Это необходимо для корректной работы свайпа на клиенте
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

        \Log::info('=== getSlidesData RESULT ===', [
            'total_days_returned' => count($daysData),
        ]);

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
            'reply_to_id' => $replyToId,
        ] = $request->validate([
            'timesheet_contract_id' => 'required|integer',
            'message' => 'required|string',
            'reply_to_id' => 'nullable|integer',
        ]);

        $contract = TimesheetContract::find($timesheetContractId);

        if (!$contract) {
            return response()->json(['error' => 'Contract not found'], 404);
        }

        $comment = $contract->chat()->create([
            'from_id' => $request->user()->staff_id,
            'message' => $message,
            'reply_to_id' => $replyToId,
        ]);

        $comment->load('profile.registred');

        return new TimesheetChatResource($comment);
    }

    /**
     * Обновить комментарий
     *
     * @param Request $request
     * @param int $id
     * @return TimesheetChatResource|\Illuminate\Http\JsonResponse
     */
    public function updateComment(Request $request, $id) {
        [
            'message' => $message,
        ] = $request->validate([
            'message' => 'required|string',
        ]);

        $timesheetMess = TimesheetChat::find($id);

        if (!$timesheetMess) {
            return response()->json(['error' => 'Comment not found'], 404);
        }

        // Проверяем, что пользователь - автор комментария
        if ($timesheetMess->from_id !== $request->user()->staff_id) {
            return response()->json(['error' => 'У вас нет прав для редактирования этого комментария'], 403);
        }

        $timesheetMess->message = $message;
        $timesheetMess->save();

        $timesheetMess->load('profile.registred');

        return new TimesheetChatResource($timesheetMess);
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

    /**
     * Получить все уникальные команды и контракты для фильтров
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilterOptions() {
        \Log::info('=== getFilterOptions START ===');

        // Получаем все уникальные staff_id из TimesheetTeam
        $uniqueStaffIds = TimesheetTeam::select('staff_id')
            ->groupBy('staff_id')
            ->pluck('staff_id');

        \Log::info('Unique staff IDs found:', ['count' => $uniqueStaffIds->count()]);

        // Загружаем профили для уникальных staff_id
        $teams = Staff::select(['id', 'sname', 'fname', 'mname'])
            ->whereIn('id', $uniqueStaffIds)
            ->get()
            ->map(function($staff) {
                return [
                    'id' => $staff->id,
                    'name' => trim("{$staff->sname} {$staff->fname} {$staff->mname}"),
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        // Получаем все уникальные contract_id из TimesheetContract
        $uniqueContractIds = TimesheetContract::select('contract_id')
            ->groupBy('contract_id')
            ->pluck('contract_id');

        \Log::info('Unique contract IDs found:', ['count' => $uniqueContractIds->count()]);

        // Загружаем контракты для уникальных contract_id
        $contracts = ContractModel::select(['id', 'title', 'titul', 'object_number'])
            ->whereIn('id', $uniqueContractIds)
            ->get()
            ->map(function($contract) {
                return [
                    'id' => $contract->id,
                    'name' => $contract->title ?: $contract->titul,
                    'object_number' => $contract->object_number,
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        \Log::info('=== getFilterOptions RESULT ===', [
            'teams_count' => $teams->count(),
            'contracts_count' => $contracts->count(),
        ]);

        return response()->json([
            'teams' => $teams,
            'contracts' => $contracts,
        ]);
    }

    /**
     * Добавить/удалить реакцию на комментарий (toggle)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleReaction(Request $request) {
        [
            'comment_id' => $commentId,
            'emoji' => $emoji,
        ] = $request->validate([
            'comment_id' => 'required|integer',
            'emoji' => 'required|string',
        ]);

        $comment = TimesheetChat::find($commentId);

        if (!$comment) {
            return response()->json(['error' => 'Comment not found'], 404);
        }

        $userId = $request->user()->id;
        $reactions = $comment->reactions ?? [];

        // Проверяем, есть ли уже реакция от этого пользователя с таким же эмодзи
        $reactionIndex = null;
        foreach ($reactions as $index => $reaction) {
            if ($reaction['user_id'] == $userId && $reaction['emoji'] === $emoji) {
                $reactionIndex = $index;
                break;
            }
        }

        if ($reactionIndex !== null) {
            // Удаляем существующую реакцию
            array_splice($reactions, $reactionIndex, 1);
        } else {
            // Добавляем новую реакцию
            $reactions[] = [
                'user_id' => $userId,
                'emoji' => $emoji,
            ];
        }

        $comment->reactions = $reactions;
        $comment->save();

        return response()->json([
            'success' => true,
            'reactions' => $reactions,
        ]);
    }
}
