<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Production\Reports\ReportPresenter;
use App\Production\Reports\ReportQueries;
use App\Production\Reports\ReportService;
use App\Production\Support\OrgClock;
use App\Production\Support\ProductionException;
use App\Production\Tasks\TaskPresenter;
use App\Production\Tasks\Viewer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Отчёты по заданиям и готовность объекта (ТЗ, раздел 8) */
class ProductionReportsController extends Controller
{
    public function __construct(
        private readonly OrgClock $clock,
        private readonly ReportService $reports,
        private readonly ReportPresenter $presenter,
        private readonly ReportQueries $queries,
        private readonly TaskPresenter $tasks,
    ) {
    }

    /** Экран отчёта: отчёт (или null — ещё не создан), план и согласованное по объекту, права */
    public function show(Request $request, int $id): JsonResponse
    {
        $viewer = $this->viewer($request);

        return response()->json($this->screen($viewer, $id));
    }

    /** Создать или изменить отчёт автором, отправить (в том числе пришедшее без связи, п. 8.6) */
    public function save(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'client_uuid' => 'nullable|string|max:36',
            'offline' => 'sometimes|boolean',
            'submit' => 'sometimes|boolean',
            ...$this->contentRules(),
            'object_percent_prefilled' => 'sometimes|nullable',
        ]);
        $viewer = $this->viewer($request);
        [, $warnings] = $this->reports->save($viewer, $id, $data, $this->clock->today());

        return response()->json($this->screen($viewer, $id) + ['warnings' => $warnings]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['revision' => 'required|integer', ...$this->contentRules()]);
        $viewer = $this->viewer($request);
        $this->reports->approve($viewer, $id, $data, $this->clock->today());

        return response()->json($this->screen($viewer, $id));
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['revision' => 'required|integer', 'comment' => 'nullable|string|max:2000']);
        $viewer = $this->viewer($request);
        $this->reports->reject($viewer, $id, $data);

        return response()->json($this->screen($viewer, $id));
    }

    /** Правка согласованного (право 10) с решением «применить исправленный процент» */
    public function correct(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'revision' => 'required|integer',
            'apply_object_percent' => 'sometimes|boolean',
            ...$this->contentRules(),
        ]);
        $viewer = $this->viewer($request);
        $this->reports->correct($viewer, $id, $data);

        return response()->json($this->screen($viewer, $id));
    }

    public function addPhoto(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'photo' => 'required|file|mimes:jpeg,jpg,png,webp,heic|max:20480',
            'client_uuid' => 'nullable|string|max:36',
            'offline' => 'sometimes|boolean',
        ]);
        $viewer = $this->viewer($request);
        $photo = $this->reports->addPhoto($viewer, $id, $request->file('photo'), $data);

        return response()->json($this->screen($viewer, $id) + ['photo_id' => (int) $photo->id], 201);
    }

    public function removePhoto(Request $request, int $id, int $photoId): JsonResponse
    {
        $viewer = $this->viewer($request);
        $this->reports->removePhoto($viewer, $id, $photoId);

        return response()->json($this->screen($viewer, $id));
    }

    /** Прямое изменение готовности объекта (право 15) с причиной */
    public function readiness(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['percent' => 'required', 'shown' => 'nullable', 'reason' => 'required|string|max:2000']);
        $readiness = $this->reports->changeReadiness($this->viewer($request), $id, $data);

        return response()->json(['readiness' => $readiness]);
    }

    /** История выполнения объекта из шкалы в карточке задания (П-ФК-34) */
    public function objectHistory(Request $request, int $id): JsonResponse
    {
        $viewer = $this->viewer($request);
        [$task] = $this->reports->find($viewer, $id);

        return response()->json(['items' => $this->queries->objectHistory($viewer, (int) $task->contract_id)]);
    }

    /** Список на согласование (п. 8.4) */
    public function pending(Request $request): JsonResponse
    {
        $viewer = $this->viewer($request);
        $tasks = $this->queries->pending($viewer);
        $today = $this->clock->today();

        return response()->json(['items' => $this->tasks->list($tasks, $viewer, $today)]);
    }

    /** Задания без отчёта — плашка вверху текущего дня (П-ЗД-37) */
    public function due(Request $request): JsonResponse
    {
        $viewer = $this->viewer($request);
        $today = $this->clock->today();

        return response()->json(['items' => $this->tasks->list($this->queries->due($viewer, $today), $viewer, $today)]);
    }

    private function screen(Viewer $viewer, int $taskId): array
    {
        [$task, , $report] = $this->reports->find($viewer, $taskId);

        return $this->presenter->screen($task, $report, $viewer);
    }

    private function contentRules(): array
    {
        return [
            'text' => 'nullable|string|max:5000',
            'task_percent' => 'nullable',
            'object_percent' => 'nullable',
            'items' => 'present|array|max:'.ReportService::MAX_ITEMS,
            'items.*.work_type_id' => 'required|integer',
            'items.*.quantity' => 'nullable|numeric|min:0|max:99999999999',
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
