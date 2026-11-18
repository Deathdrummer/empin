<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TimesheetChatResource;
use App\Models\TimesheetChat;
use App\Models\TimesheetContract;
use App\Production\Support\OrgClock;
use App\Production\Support\ProductionException;
use App\Production\Tasks\TaskService;
use App\Production\Tasks\Viewer;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Переписка задания (мини-чат в карточке, п. 6.4): читать и писать может каждый, кто видит задание, — по любому
 * основанию (П-ЗД-20, П-ЗД-23); после отзыва приглашения доступ пропадает, сообщения остаются (П-ЗД-24).
 *
 * Сами сообщения — комментарии план-графика: сохранение, вложения, правка, удаление и реакции выполняет тот же код
 * API план-графика (TimesheetApiController), здесь — только проверка доступа к заданию. Так правила правки и удаления
 * сообщений остаются в одном месте и для сайта, и для приложения.
 */
class ProductionTaskChatController extends Controller
{
    public function __construct(
        private readonly OrgClock $clock,
        private readonly TaskService $tasks,
        private readonly TimesheetApiController $comments,
    ) {
    }

    public function index(Request $request, int $id): JsonResponse
    {
        $task = $this->task($request, $id);
        $items = TimesheetChat::query()
            ->where('timesheet_contract_id', $task->id)
            ->with(['profile.registred'])
            ->orderBy('id')
            ->get();

        return response()->json([
            'items' => TimesheetChatResource::collection($items)->resolve($request),
            // Писать может любой, кто видит задание (П-ЗД-23): отдельного права нет
            'can_write' => true,
        ]);
    }

    /**
     * Новое сообщение. client_uuid — метка отправки с телефона: повтор после обрыва связи (ИС-ЗД-16) возвращает уже
     * сохранённое сообщение, а не создаёт второе.
     */
    public function store(Request $request, int $id)
    {
        $data = $request->validate([
            'client_uuid' => 'nullable|string|max:36',
            'reply_to_id' => 'nullable|integer',
        ]);
        $task = $this->task($request, $id);
        $me = (int) $request->user()->staff_id;
        $uuid = $data['client_uuid'] ?? null;

        if ($uuid && ($existing = $this->byClientUuid($me, $uuid))) {
            return (new TimesheetChatResource($existing->load('profile.registred')))->response()->setStatusCode(200);
        }
        // Ответ — только на сообщение этого же задания: цитата не должна раскрывать переписку другого задания
        if (!empty($data['reply_to_id']) && !TimesheetChat::whereKey($data['reply_to_id'])->where('timesheet_contract_id', $task->id)->exists()) {
            throw ProductionException::invalid('Ответить можно только на сообщение этого задания');
        }

        $request->merge(['timesheet_contract_id' => $task->id]);
        $response = $this->comments->addComment($request);
        if (!$response instanceof TimesheetChatResource) {
            return $response;
        }

        $comment = $response->resource;
        if ($uuid) {
            try {
                $comment->forceFill(['client_uuid' => $uuid])->saveQuietly();
            } catch (QueryException $e) {
                // Два повтора пришли одновременно: оставляем первое сохранённое
                if (($e->errorInfo[1] ?? null) !== 1062) {
                    throw $e;
                }
                $comment->delete();

                return (new TimesheetChatResource($this->byClientUuid($me, $uuid)->load('profile.registred')))->response()->setStatusCode(200);
            }
        }

        return $response->response()->setStatusCode(201);
    }

    public function update(Request $request, int $id, int $commentId)
    {
        $this->comment($request, $id, $commentId);

        return $this->comments->updateComment($request, $commentId);
    }

    public function destroy(Request $request, int $id, int $commentId)
    {
        $this->comment($request, $id, $commentId);

        return $this->comments->removeComment($request, $commentId);
    }

    public function reaction(Request $request, int $id, int $commentId)
    {
        $request->validate(['emoji' => 'required|string|max:16']);
        $this->comment($request, $id, $commentId);
        $request->merge(['comment_id' => $commentId]);

        return $this->comments->toggleReaction($request);
    }

    /** Задание, которое сотрудник видит, иначе 404 — существование чужого задания не раскрывается */
    private function task(Request $request, int $id): TimesheetContract
    {
        $staffId = (int) $request->user()->staff_id;
        if (!$staffId) {
            throw ProductionException::forbidden('Учётная запись не связана с сотрудником ERP');
        }

        return $this->tasks->visible(Viewer::load($staffId, $this->clock->today()), $id);
    }

    /** Сообщение этого задания; сообщение другого задания по этому адресу не находится */
    private function comment(Request $request, int $id, int $commentId): TimesheetChat
    {
        $task = $this->task($request, $id);

        return TimesheetChat::whereKey($commentId)->where('timesheet_contract_id', $task->id)->first()
            ?? throw ProductionException::notFound('Сообщение не найдено');
    }

    private function byClientUuid(int $staffId, string $uuid): ?TimesheetChat
    {
        return TimesheetChat::where('from_id', $staffId)->where('client_uuid', $uuid)->first();
    }
}
