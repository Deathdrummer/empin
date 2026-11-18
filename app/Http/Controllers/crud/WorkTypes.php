<?php namespace App\Http\Controllers\crud;

use App\Http\Controllers\Controller;
use App\Models\Erp\WorkType;
use App\Production\Erp\ErpAudit;
use App\Production\Erp\WorkTypeRegistry;
use App\Traits\HasCrudController;
use Illuminate\Http\Request;

/** Раздел админки ERP «Производство → Виды работ» («Доработки ERP», п. 5) */
class WorkTypes extends Controller {
	use HasCrudController;

	public const SECTION_ABILITY = 'section-production.work_types:admin';

	protected $data = [];

	public function __construct(private readonly WorkTypeRegistry $registry) {
		// Как и страница раздела (get_section): главный администратор — всегда, остальные — с правом на раздел
		$this->middleware(function ($request, $next) {
			abort_unless($request->user('admin')?->can(self::SECTION_ABILITY), 403);

			return $next($request);
		});
	}

	public function index(Request $request) {
		['views' => $viewPath] = $request->validate(['views' => 'string|required']);
		$types = WorkType::orderBy('name')->get();
		$ids = $types->pluck('id')->all();
		$usage = $this->registry->usage($ids);
		$used = array_flip($this->registry->usedIds($ids));
		$list = $types->map(fn (WorkType $type) => $this->row($type, $usage[$type->id] ?? ['plans' => 0, 'tasks' => 0], isset($used[$type->id])));

		return $this->view($viewPath.'.list', ['list' => $list, 'itemView' => $viewPath.'.item']);
	}

	public function create(Request $request) {
		['views' => $viewPath, 'newItemIndex' => $index] = $request->validate(['views' => 'string|required', 'newItemIndex' => 'numeric|required']);

		return $this->view($viewPath.'.new', ['index' => $index]);
	}

	public function store_show(Request $request) {
		$valid = $request->validate(['views' => 'string|required', 'name' => 'string|required|max:255', 'unit' => 'string|required|max:40']);
		$type = $this->registry->create($valid['name'], $valid['unit']);

		return $this->view($valid['views'].'.item', $this->row($type, ['plans' => 0, 'tasks' => 0], false));
	}

	public function update(Request $request, $id) {
		// Поле единицы у использованного вида работ заблокировано и не приходит — единица остаётся прежней
		$valid = $request->validate(['name' => 'string|required|max:255', 'unit' => 'nullable|string|max:40']);
		$type = WorkType::findOrFail($id);
		$this->registry->update($type, $valid['name'], $valid['unit'] ?? $type->unit);

		return response()->json(['updated' => true]);
	}

	public function destroy(?int $id = null) {
		$this->registry->delete(WorkType::findOrFail($id));

		return response()->json(['deleted' => true]);
	}

	public function history(Request $request) {
		['work_type' => $id, 'views' => $viewPath] = $request->validate(['work_type' => 'required|integer', 'views' => 'string|required']);
		WorkType::findOrFail($id);

		return $this->view($viewPath.'.history', ['history' => ErpAudit::history(WorkTypeRegistry::ENTITY, (int) $id)]);
	}

	private function row(WorkType $type, array $usage, bool $used): array {
		return [
			'id' => $type->id,
			'name' => $type->name,
			'unit' => $type->unit,
			'plans' => $usage['plans'],
			'tasks' => $usage['tasks'],
			// Единица уже использованного вида работ не меняется (см. WorkTypeRegistry)
			'unit_locked' => $used,
		];
	}
}
