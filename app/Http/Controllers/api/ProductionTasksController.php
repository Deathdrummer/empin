<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Production\Attendance\AttendanceService;
use App\Production\Reports\ReportPresenter;
use App\Production\Reports\ReportService;
use App\Production\Support\OrgClock;
use App\Production\Support\ProductionException;
use App\Production\Tasks\TaskAccess;
use App\Production\Tasks\TaskFacts;
use App\Production\Tasks\TaskFilterOptions;
use App\Production\Tasks\TaskPresenter;
use App\Production\Tasks\TaskService;
use App\Production\Tasks\Viewer;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Задания и календарь (ТЗ, раздел 6) */
class ProductionTasksController extends Controller
{
    /** Окно календаря выбора даты: открытый месяц и по три месяца в обе стороны */
    private const DATES_MAX_DAYS = 220;

    public function __construct(
        private readonly OrgClock $clock,
        private readonly TaskService $tasks,
        private readonly TaskPresenter $presenter,
        private readonly TaskAccess $access,
        private readonly AttendanceService $attendance,
        private readonly ReportPresenter $reports,
        private readonly ReportService $reportService,
    ) {
    }

    /** Календарь: задания за день или период (для работы без связи приложение подгружает несколько дней, п. 9.1) */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => 'required_without:from|date_format:Y-m-d',
            'from' => 'required_without:date|date_format:Y-m-d',
            'to' => 'required_with:from|date_format:Y-m-d|after_or_equal:from',
            ...$this->filterRules(),
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

    /** Даты с заданиями за период — для выбора даты в календаре при включённом фильтре */
    public function dates(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => 'required|date_format:Y-m-d',
            'to' => 'required|date_format:Y-m-d|after_or_equal:from',
            ...$this->filterRules(),
        ]);
        $from = CarbonImmutable::parse($data['from']);
        $to = CarbonImmutable::parse($data['to']);
        if ($from->diffInDays($to) > self::DATES_MAX_DAYS) {
            throw ProductionException::invalid('Слишком большой период: не больше '.self::DATES_MAX_DAYS.' дней');
        }

        $dates = $this->tasks->calendar($this->viewer($request), $from, $to, $data)
            ->reorder()
            ->select('t.day')
            ->distinct()
            ->orderBy('t.day')
            ->pluck('day')
            ->map(fn ($day) => substr((string) $day, 0, 10))
            ->values();

        return response()->json(['dates' => $dates]);
    }

    /** Варианты фильтров календаря — только из видимых сотруднику заданий */
    public function filters(Request $request, TaskFilterOptions $options): JsonResponse
    {
        return response()->json($options->for($this->viewer($request)));
    }

    /** Карточка задания: содержание, история изменений и исполнителей, блок «Выход»; приглашённые — обладателю права 7 */
    public function show(Request $request, int $id): JsonResponse
    {
        $viewer = $this->viewer($request);
        $task = $this->tasks->visible($viewer, $id);
        $canInvite = $this->access->canInvite($viewer, TaskFacts::fromModel($task));
        $report = $task->report;

        return response()->json([
            'task' => $this->presenter->one($task, $viewer, $this->clock->today()),
            'invites' => $canInvite ? $this->tasks->invites($task) : null,
            'history' => $this->presenter->history($task->id),
            // Блок «Выход» (П-ЗД-39…43); нет бригады и водителя — блока нет
            'attendance' => $this->attendance->block($viewer, $task),
            // Отчёт и шкалы задания и объекта (п. 8.4); выбор по готовности при удалении (П-ФК-40)
            'report' => $report ? $this->reportSummary($report) : null,
            'progress' => $this->reports->progress($task, $report, $viewer),
            'delete_choice' => $report ? $this->reportService->deleteChoice($task, $report) : null,
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
        $data = $request->validate(['readiness' => 'nullable|in:keep,restore']);
        $this->tasks->delete($this->viewer($request), $id, $this->clock->today(), $data['readiness'] ?? null);

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

    /** Фильтры календаря (п. 6.4): объект, бригада или сотрудник-исполнитель, создатель, техника, водитель */
    private function filterRules(): array
    {
        return [
            'object_id' => 'nullable|integer',
            'brigade_id' => 'nullable|integer',
            'executor_staff_id' => 'nullable|integer',
            'creator_id' => 'nullable|integer',
            'vehicle_id' => 'nullable|integer',
            'driver_staff_id' => 'nullable|integer',
        ];
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
            // Смена объекта: готовность прежнего и нового объекта из того же окна (П-ЗД-29)
            'readiness' => 'sometimes|nullable|array',
            'readiness.old' => 'sometimes|nullable|array',
            'readiness.new' => 'sometimes|nullable|array',
        ];
    }

    /** Отчёт в карточке задания: статус и кто что сделал (п. 8.4) */
    private function reportSummary(\App\Models\TaskReport $report): array
    {
        $full = $this->reports->report($report);

        return array_intersect_key($full, array_flip([
            'id', 'status', 'author', 'submitted_by', 'submitted_at', 'decided_by', 'decided_at', 'reject_comment', 'edited_by', 'edited_at',
        ]));
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
