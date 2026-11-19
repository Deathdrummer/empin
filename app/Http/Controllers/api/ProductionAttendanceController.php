<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Production\Attendance\AttendanceService;
use App\Production\Support\OrgClock;
use App\Production\Tasks\TaskService;
use App\Production\Tasks\Viewer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Выход на смену (ТЗ, раздел 7): плашка, самоотметка, заявка состава, подтверждение в задании */
class ProductionAttendanceController extends Controller
{
    public function __construct(
        private readonly OrgClock $clock,
        private readonly AttendanceService $attendance,
        private readonly TaskService $tasks,
    ) {
    }

    /** Плашка «Выход на смену» на сегодня */
    public function today(Request $request): JsonResponse
    {
        return response()->json($this->attendance->today($this->viewer($request)));
    }

    /** Самоотметка; без связи — по времени нажатия (П-СМ-03а) */
    public function mark(Request $request): JsonResponse
    {
        $data = $request->validate([
            'as_driver' => 'nullable|boolean',
            'brigade_id' => 'nullable|integer',
            ...$this->offlineRules(),
        ]);
        $viewer = $this->viewer($request);
        $this->attendance->mark($viewer, $data);

        return response()->json($this->attendance->today($viewer));
    }

    /** Заявка состава бригадиром (право 16) */
    public function crew(Request $request): JsonResponse
    {
        $data = $request->validate([
            'brigade_id' => 'required|integer',
            'staff_ids' => 'present|array',
            'staff_ids.*' => 'integer',
            ...$this->offlineRules(),
        ]);
        $viewer = $this->viewer($request);
        $this->attendance->declareCrew($viewer, (int) $data['brigade_id'], $data['staff_ids'], $data);

        return response()->json($this->attendance->today($viewer));
    }

    /** Подтверждение выхода в блоке «Выход» задания: состав бригады и/или водитель */
    public function confirm(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'staff_ids' => 'nullable|array',
            'staff_ids.*' => 'integer',
            'driver' => 'nullable|boolean',
            'shown_staff_ids' => 'nullable|array',
            'shown_staff_ids.*' => 'integer',
            ...$this->offlineRules(),
        ]);
        $viewer = $this->viewer($request);
        $task = $this->tasks->visible($viewer, $id);
        $staffIds = array_key_exists('staff_ids', $data) && $data['staff_ids'] !== null ? array_map('intval', $data['staff_ids']) : null;

        return response()->json(['attendance' => $this->attendance->confirm($viewer, $task, $staffIds, (bool) ($data['driver'] ?? false), $data)]);
    }

    /** Действие, сделанное без связи: время нажатия и часы телефона в момент отправки (п. 9.1, правило 2) */
    private function offlineRules(): array
    {
        return [
            'client_uuid' => 'nullable|string|max:64',
            'offline' => 'nullable|boolean',
            'pressed_at' => 'nullable|date',
            'device_now' => 'nullable|date',
        ];
    }

    private function viewer(Request $request): Viewer
    {
        return Viewer::load((int) $request->user()->staff_id, $this->clock->today());
    }
}
