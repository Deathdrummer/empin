<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Production\Support\OrgClock;
use App\Production\Support\ProductionException;
use App\Production\Tasks\TaskAccess;
use App\Production\Tasks\TaskFacts;
use App\Production\Tasks\TaskPresenter;
use App\Production\Tasks\TaskService;
use App\Production\Tasks\Viewer;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Задания и календарь (ТЗ, раздел 6). В ответах — признаки доступа (`abilities`), чтобы приложение показывало кнопки
 * только при наличии права (п. 4.4); окончательная проверка — здесь, при каждом действии (п. 9.1, правило 4).
 */
class ProductionTasksController extends Controller
{
    public function __construct(
        private readonly OrgClock $clock,
        private readonly TaskService $tasks,
        private readonly TaskPresenter $presenter,
        private readonly TaskAccess $access,
    ) {
    }

    /** Календарь: задания за день или период (для работы без связи приложение подгружает несколько дней, п. 9.1) */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => 'required_without:from|date_format:Y-m-d',
            'from' => 'required_without:date|date_format:Y-m-d',
            'to' => 'required_with:from|date_format:Y-m-d|after_or_equal:from',
            'object_id' => 'nullable|integer',
            'brigade_id' => 'nullable|integer',
            'executor_staff_id' => 'nullable|integer',
            'creator_id' => 'nullable|integer',
            'vehicle_id' => 'nullable|integer',
            'driver_staff_id' => 'nullable|integer',
        ]);
        $from = CarbonImmutable::parse($data['date'] ?? $data['from']);
        $to = CarbonImmutable::parse($data['date'] ?? $data['to']);
        if ($from->diffInDays($to) >= (int) config('production.calendar_max_days')) {
            throw ProductionException::invalid('Слишком большой период: не больше '.config('production.calendar_max_days').' дней');
        }

        $viewer = $this->viewer($request);
        $today = $this->clock->today();
        $tasks = $this->tasks->calendar($viewer, $from, $to, $data)->get();

        return response()->json([
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'today' => $today->toDateString(),
            'rights_version' => $viewer->rights->version(),
            'items' => $this->presenter->list($tasks, $viewer, $today),
        ]);
    }

    /** Карточка задания: содержание, история изменений и исполнителей; приглашённые — обладателю права 7 */
    public function show(Request $request, int $id): JsonResponse
    {
        $viewer = $this->viewer($request);
        $task = $this->tasks->visible($viewer, $id);
        $canInvite = $this->access->canInvite($viewer, TaskFacts::fromModel($task));

        return response()->json([
            'task' => $this->presenter->one($task, $viewer, $this->clock->today()),
            'invites' => $canInvite ? $this->tasks->invites($task) : null,
            'history' => $this->presenter->history($task->id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules(creating: true));
        $viewer = $this->viewer($request);
        $today = $this->clock->today();
        [$task, $warnings] = $this->tasks->create($viewer, $data, $today);

        return response()->json(['task' => $this->presenter->one($task, $viewer, $today), 'warnings' => $warnings], 201);
    }

    public function copy(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['date' => 'required|date_format:Y-m-d']);
        $viewer = $this->viewer($request);
        $today = $this->clock->today();
        [$task, $warnings] = $this->tasks->copy($viewer, $id, $data['date'], $today);

        return response()->json(['task' => $this->presenter->one($task, $viewer, $today), 'warnings' => $warnings], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate($this->rules(creating: false));
        $viewer = $this->viewer($request);
        $today = $this->clock->today();
        [$task, $warnings] = $this->tasks->update($viewer, $id, $data, $today);

        return response()->json(['task' => $this->presenter->one($task, $viewer, $today), 'warnings' => $warnings]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->tasks->delete($this->viewer($request), $id, $this->clock->today());

        return response()->json(['deleted' => true]);
    }

    public function invite(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['staff_id' => 'required|integer']);
        $viewer = $this->viewer($request);
        $this->tasks->invite($viewer, $id, (int) $data['staff_id']);

        return response()->json(['invites' => $this->tasks->invites($this->tasks->visible($viewer, $id))]);
    }

    public function revokeInvite(Request $request, int $id, int $staffId): JsonResponse
    {
        $viewer = $this->viewer($request);
        $this->tasks->revokeInvite($viewer, $id, $staffId);

        return response()->json(['invites' => $this->tasks->invites($this->tasks->visible($viewer, $id))]);
    }

    private function rules(bool $creating): array
    {
        $required = $creating ? 'required' : 'sometimes';

        return [
            'object_id' => "$required|integer",
            'date' => "$required|date_format:Y-m-d",
            // Исполнитель — бригада либо сотрудник (П-ЗД-12)
            'brigade_id' => ($creating ? 'required_without:executor_staff_id' : 'nullable').'|prohibits:executor_staff_id|integer',
            'executor_staff_id' => ($creating ? 'required_without:brigade_id' : 'nullable').'|integer',
            'text' => 'nullable|string|max:5000',
            'works' => 'sometimes|nullable|array|max:200',
            'works.*.work_type_id' => 'required|integer',
            'works.*.quantity' => 'nullable|numeric|min:0|max:99999999999',
            'vehicle_id' => 'sometimes|nullable|integer',
            'driver_id' => 'sometimes|nullable|integer',
        ];
    }

    private function viewer(Request $request): Viewer
    {
        $staffId = (int) $request->user()->staff_id;
        if (!$staffId) {
            throw ProductionException::forbidden('Учётная запись не связана с сотрудником ERP');
        }

        return Viewer::load($staffId, $this->clock->today());
    }
}
