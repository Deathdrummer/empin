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
use Illuminate\Support\Facades\Storage;

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
            ->with([
                'profile',
                'contracts' => function($q) use ($hasContractsFilter, $filters) {
                    // Фильтруем контракты если указан фильтр
                    if ($hasContractsFilter) {
                        $q->whereIn('contract_id', $filters['contracts']);
                    }
                },
                'contracts.contract',
                'contracts.chat.profile.registred',
            ])
            ->get();

        $teams = $teams->groupBy(fn($team) => $team->day instanceof Carbon ? $team->day->toDateString() : $team->day);

        $teams = $teams->map(fn($group) => TimesheetTeamResource::collection($group)->resolve());

        $daysData = [];
        foreach ($indexes as $idx) {
            $dateObj = DdrDateTime::getOffsetDate($idx);
            $day = $dateObj->toDateString();
            $weekDayNum = (int)DdrDateTime::numOfWeek($dateObj);

            // При фильтрации показываем ТОЛЬКО дни с совпадениями
            if ($hasActiveFilters && (!isset($teams[$day]) || empty($teams[$day]))) {
                continue;
            }

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
        $validated = $request->validate([
            'timesheet_contract_id' => 'required|integer',
            'message' => 'nullable|string',
            'reply_to_id' => 'nullable|integer',
            'media' => 'nullable|array', // Принимаем массив файлов
            'media.*' => 'file|mimes:jpeg,jpg,png,gif,bmp,webp,mp4,mov,avi,mkv,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,7z,txt,csv,mp3,wav,ogg,aac,flac,m4a|max:51200', // max 50MB на файл
        ]);

        $timesheetContractId = $validated['timesheet_contract_id'];
        $message = $validated['message'] ?? '';
        $replyToId = $validated['reply_to_id'] ?? null;

        // Проверяем, что есть хотя бы сообщение или медиа
        if (empty(trim($message)) && !$request->hasFile('media')) {
            return response()->json(['error' => 'Необходимо указать текст комментария или прикрепить медиа'], 422);
        }

        $contract = TimesheetContract::find($timesheetContractId);

        if (!$contract) {
            return response()->json(['error' => 'Contract not found'], 404);
        }

        // Обработка медиа файлов
        $mediaArray = [];
        if ($request->hasFile('media')) {
            $files = $request->file('media');

            foreach ($files as $file) {
                $mimeType = $file->getMimeType();
                $size = $file->getSize();
                $originalFilename = $file->getClientOriginalName();

                // Сохраняем файл через Storage API в storage/app/public/timesheet/comments/
                $storagePath = $file->store('timesheet/comments', 'public');

                // Определяем тип медиа по MIME типу
                $type = 'file'; // По умолчанию
                if (str_starts_with($mimeType, 'image/')) {
                    $type = 'image';
                } elseif (str_starts_with($mimeType, 'video/')) {
                    $type = 'video';
                } elseif (str_starts_with($mimeType, 'audio/')) {
                    $type = 'audio';
                } elseif (in_array($mimeType, [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-powerpoint',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'text/plain',
                    'text/csv',
                ])) {
                    $type = 'document';
                } elseif (in_array($mimeType, [
                    'application/zip',
                    'application/x-rar-compressed',
                    'application/x-7z-compressed',
                ])) {
                    $type = 'archive';
                }

                // Формируем данные о медиа
                $mediaArray[] = [
                    'path' => '/storage/' . $storagePath, // Путь для доступа через веб
                    'type' => $type,
                    'mime_type' => $mimeType,
                    'size' => $size,
                    'name' => $originalFilename,
                ];
            }
        }

        $comment = $contract->chat()->create([
            'from_id' => $request->user()->staff_id,
            'message' => $message,
            'reply_to_id' => $replyToId,
            'media' => !empty($mediaArray) ? $mediaArray : null,
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

        // Удаляем медиа файлы если они есть
        if ($timesheetMess->media) {
            // Поддержка старого формата (одно медиа как объект) и нового (массив медиа)
            $mediaArray = [];
            if (is_array($timesheetMess->media)) {
                // Проверяем, является ли это массивом медиа или одним объектом медиа
                if (isset($timesheetMess->media['path'])) {
                    // Старый формат - одно медиа
                    $mediaArray = [$timesheetMess->media];
                } else {
                    // Новый формат - массив медиа
                    $mediaArray = $timesheetMess->media;
                }
            }

            // Удаляем все файлы
            foreach ($mediaArray as $media) {
                if (isset($media['path'])) {
                    $filePath = $media['path'];
                    // Убираем префикс /storage/ чтобы получить путь в storage/app/public/
                    $storageFilePath = str_replace('/storage/', '', $filePath);

                    if (Storage::disk('public')->exists($storageFilePath)) {
                        Storage::disk('public')->delete($storageFilePath);
                    }
                }
            }
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
        // Получаем бригады с сортировкой по дню добавления
        // Сначала группируем по дню (DATE), затем внутри дня - по времени (DESC)
        $teams = Staff::select(['staff.id', 'staff.sname', 'staff.fname', 'staff.mname'])
            ->join('timesheet_teams', 'staff.id', '=', 'timesheet_teams.staff_id')
            ->selectRaw('MAX(timesheet_teams.created_at) as last_added')
            ->groupBy('staff.id', 'staff.sname', 'staff.fname', 'staff.mname')
            ->orderByRaw('DATE(last_added) DESC, last_added DESC')
            ->limit(1000)
            ->get()
            ->map(function($staff) {
                return [
                    'id' => $staff->id,
                    'name' => trim("{$staff->sname} {$staff->fname} {$staff->mname}"),
                ];
            });

        // Получаем объекты с сортировкой по дню добавления
        // Сначала группируем по дню (DATE), затем внутри дня - по времени (DESC)
        $contracts = ContractModel::select(['contracts.id', 'contracts.title', 'contracts.titul', 'contracts.object_number'])
            ->join('timesheet_contracts', 'contracts.id', '=', 'timesheet_contracts.contract_id')
            ->selectRaw('MAX(timesheet_contracts.created_at) as last_added')
            ->groupBy('contracts.id', 'contracts.title', 'contracts.titul', 'contracts.object_number')
            ->orderByRaw('DATE(last_added) DESC, last_added DESC')
            ->limit(1000)
            ->get()
            ->map(function($contract) {
                return [
                    'id' => $contract->id,
                    'name' => $contract->title ?: $contract->titul,
                    'object_number' => $contract->object_number,
                    'in_teams' => true, // Все объекты из этого метода присутствуют в бригадах
                ];
            });

        return response()->json([
            'teams' => $teams,
            'contracts' => $contracts,
        ]);
    }

    /**
     * Поиск бригад для фильтров
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchTeams(Request $request) {
        [
            'search' => $search,
        ] = $request->validate([
            'search' => 'nullable|string',
        ]);

        $query = Staff::select(['staff.id', 'staff.sname', 'staff.fname', 'staff.mname'])
            ->join('timesheet_teams', 'staff.id', '=', 'timesheet_teams.staff_id')
            ->selectRaw('MAX(timesheet_teams.created_at) as last_added')
            ->groupBy('staff.id', 'staff.sname', 'staff.fname', 'staff.mname');

        // Применяем поиск если указан
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('staff.sname', 'like', '%'.$search.'%')
                  ->orWhere('staff.fname', 'like', '%'.$search.'%')
                  ->orWhere('staff.mname', 'like', '%'.$search.'%');
            });
        }

        $teams = $query
            ->orderByRaw('DATE(last_added) DESC, last_added DESC')
            ->limit(1000)
            ->get()
            ->map(function($staff) {
                return [
                    'id' => $staff->id,
                    'name' => trim("{$staff->sname} {$staff->fname} {$staff->mname}"),
                ];
            });

        return response()->json($teams);
    }

    /**
     * Поиск контрактов для фильтров
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchContracts(Request $request) {
        [
            'search' => $search,
        ] = $request->validate([
            'search' => 'nullable|string',
        ]);

        $query = ContractModel::select(['contracts.id', 'contracts.title', 'contracts.titul', 'contracts.object_number'])
            ->join('timesheet_contracts', 'contracts.id', '=', 'timesheet_contracts.contract_id')
            ->selectRaw('MAX(timesheet_contracts.created_at) as last_added')
            ->groupBy('contracts.id', 'contracts.title', 'contracts.titul', 'contracts.object_number');

        // Применяем поиск если указан
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('contracts.object_number', 'like', '%'.$search.'%')
                  ->orWhere('contracts.title', 'like', '%'.$search.'%')
                  ->orWhere('contracts.titul', 'like', '%'.$search.'%');
            });
        }

        $contracts = $query
            ->orderByRaw('DATE(last_added) DESC, last_added DESC')
            ->limit(1000)
            ->get()
            ->map(function($contract) {
                return [
                    'id' => $contract->id,
                    'name' => $contract->title ?: $contract->titul,
                    'object_number' => $contract->object_number,
                    'in_teams' => true, // Все объекты из этого метода присутствуют в бригадах
                ];
            });

        return response()->json($contracts);
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

    /**
     * Поиск ВСЕХ контрактов (включая не добавленные в бригады)
     * с пометкой о присутствии в табеле
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchAllContracts(Request $request) {
        [
            'search' => $search,
        ] = $request->validate([
            'search' => 'nullable|string',
        ]);

        // Получаем ID всех контрактов, которые присутствуют в табеле
        $contractsInTeams = TimesheetContract::select('contract_id')
            ->distinct()
            ->pluck('contract_id')
            ->toArray();

        // Ищем ВСЕ контракты в БД по object_number
        $query = ContractModel::select(['id', 'title', 'titul', 'object_number']);

        // Применяем поиск только по object_number (номер объекта)
        if (!empty($search)) {
            $query->where('object_number', 'like', '%'.$search.'%');
        }

        $contracts = $query
            ->limit(1000)
            ->get()
            ->map(function($contract) use ($contractsInTeams) {
                return [
                    'id' => $contract->id,
                    'name' => $contract->title ?: $contract->titul,
                    'object_number' => $contract->object_number,
                    'in_teams' => in_array($contract->id, $contractsInTeams), // Присутствует ли в бригадах
                ];
            });

        return response()->json($contracts);
    }
}
