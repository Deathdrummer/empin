<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Production\Support\OrgClock;
use App\Production\Tasks\Viewer;
use App\Production\Turv\TurvExport;
use App\Production\Turv\TurvService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** ТУРВ (ТЗ, раздел 7): табель месяца, корректировка ячейки, порядок, выгрузка XLSX и PDF */
class ProductionTurvController extends Controller
{
    public function __construct(private readonly OrgClock $clock, private readonly TurvService $turv)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['month' => 'required|date_format:Y-m']);

        return response()->json($this->turv->month($this->viewer($request), $data['month']) + ['pdf' => TurvExport::pdfAvailable()]);
    }

    /** Поставить или снять выход в ячейке (право 18); только при связи (п. 9.1) */
    public function cell(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kind' => 'required|in:brigade,driver',
            'brigade_id' => 'required_if:kind,brigade|nullable|integer',
            'staff_id' => 'required|integer',
            'date' => 'required|date_format:Y-m-d',
            'present' => 'required|boolean',
            'notify' => 'nullable|boolean',
        ]);
        $table = $this->turv->correct($this->viewer($request), $data['kind'], isset($data['brigade_id']) ? (int) $data['brigade_id'] : null,
            (int) $data['staff_id'], $data['date'], (bool) $data['present'], (bool) ($data['notify'] ?? false));

        return response()->json($table + ['pdf' => TurvExport::pdfAvailable()]);
    }

    public function order(Request $request): JsonResponse
    {
        $data = $request->validate([
            'brigade_ids' => 'present|array',
            'brigade_ids.*' => 'integer',
            'rows' => 'present|array',
        ]);
        $this->turv->saveOrder($this->viewer($request), $data['brigade_ids'], $data['rows']);

        return response()->json(['saved' => true]);
    }

    /** Файл — по порядку, который сейчас на экране, даже если он ещё сохраняется (инт. 8.56) */
    public function export(Request $request, TurvExport $export): Response
    {
        $data = $request->validate([
            'month' => 'required|date_format:Y-m',
            'format' => 'required|in:xlsx,pdf',
            'order' => 'nullable|json',
        ]);
        $order = isset($data['order']) ? json_decode($data['order'], true) : null;
        [$content, $name, $type] = $export->build($this->turv->month($this->viewer($request), $data['month'], is_array($order) ? $order : null), $data['format']);

        return response($content, 200, [
            'Content-Type' => $type,
            'Content-Disposition' => "attachment; filename*=UTF-8''".rawurlencode($name),
        ]);
    }

    private function viewer(Request $request): Viewer
    {
        return Viewer::load((int) $request->user()->staff_id, $this->clock->today());
    }
}
