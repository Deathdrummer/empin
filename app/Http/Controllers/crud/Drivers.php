<?php namespace App\Http\Controllers\crud;

use App\Http\Controllers\Controller;
use App\Models\Erp\Driver;
use App\Models\Erp\Vehicle;
use App\Models\Staff;
use App\Production\Erp\ErpAudit;
use App\Production\Erp\VehicleRegistry;
use App\Production\Objects\ObjectDirectory;
use App\Traits\HasCrudController;
use Illuminate\Http\Request;

/** Раздел админки ERP «Производство → Водители» — список водителей из числа сотрудников («Доработки ERP», п. 4) */
class Drivers extends Controller {
	use HasCrudController;

	public const SECTION_ABILITY = 'section-production.drivers:admin';

	protected $data = [];

	public function __construct(private readonly VehicleRegistry $registry) {
		$this->middleware(function ($request, $next) {
			abort_unless($request->user('admin')?->can(self::SECTION_ABILITY), 403);

			return $next($request);
		});
	}

	public function index(Request $request) {
		['views' => $viewPath] = $request->validate(['views' => 'string|required']);
		$this->staffOptions();
		$list = Driver::with(['staff:id,sname,fname,mname,work_post,working', 'vehicles:id,name,plate'])->get()
			->map(fn (Driver $driver) => $this->row($driver))
			->sortBy(fn (array $row) => [$row['not_used'] || $row['fired'] ? 1 : 0, $row['name']])
			->values();

		return $this->view($viewPath.'.list', ['list' => $list, 'itemView' => $viewPath.'.item']);
	}

	public function create(Request $request) {
		['views' => $viewPath, 'newItemIndex' => $index] = $request->validate(['views' => 'string|required', 'newItemIndex' => 'numeric|required']);
		$this->staffOptions();

		return $this->view($viewPath.'.new', ['index' => $index]);
	}

	public function store_show(Request $request) {
		$valid = $request->validate(['views' => 'string|required', 'staff_id' => 'required|integer']);
		$driver = $this->registry->addDriver((int) $valid['staff_id']);

		return $this->view($valid['views'].'.item', $this->row($driver->load(['staff', 'vehicles'])));
	}

	public function update(Request $request, $id) {
		$valid = $request->validate(['not_used' => 'nullable|boolean']);
		$this->registry->setDriverNotUsed(Driver::findOrFail($id), (bool) ($valid['not_used'] ?? false));

		return response()->json(['updated' => true]);
	}

	public function destroy(?int $id = null) {
		$this->registry->deleteDriver(Driver::findOrFail($id));

		return response()->json(['deleted' => true]);
	}

	public function history(Request $request) {
		['driver' => $driverId, 'views' => $viewPath] = $request->validate(['driver' => 'required|integer', 'views' => 'string|required']);
		Driver::findOrFail($driverId);

		return $this->view($viewPath.'.history', ['history' => ErpAudit::history(VehicleRegistry::DRIVER, (int) $driverId)]);
	}

	private function row(Driver $driver): array {
		return [
			'id' => $driver->id,
			'name' => ObjectDirectory::name($driver->staff?->sname, $driver->staff?->fname, $driver->staff?->mname),
			'work_post' => $driver->staff?->work_post,
			'not_used' => (bool) $driver->not_used,
			'fired' => !$driver->staff?->working,
			'vehicles' => $driver->vehicles->map(fn (Vehicle $v) => trim($v->name.' '.($v->plate ?? '')))->sort()->values()->all(),
		];
	}

	/** Работающие сотрудники, ещё не в списке водителей (удалённых можно вернуть — запись восстановится) */
	private function staffOptions(): void {
		$taken = Driver::pluck('staff_id')->all();
		$this->data['staff'] = Staff::where('working', 1)->whereNotIn('id', $taken)->orderBy('sname')->orderBy('fname')->get(['id', 'sname', 'fname', 'mname', 'work_post'])
			->map(fn (Staff $s) => ['value' => $s->id, 'title' => ObjectDirectory::name($s->sname, $s->fname, $s->mname).($s->work_post ? ' — '.$s->work_post : '')])
			->all();
	}
}
