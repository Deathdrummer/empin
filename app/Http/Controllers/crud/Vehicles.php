<?php namespace App\Http\Controllers\crud;

use App\Http\Controllers\Controller;
use App\Models\Erp\Driver;
use App\Models\Erp\Vehicle;
use App\Production\Erp\ErpAudit;
use App\Production\Erp\VehicleRegistry;
use App\Production\Objects\ObjectDirectory;
use App\Traits\HasCrudController;
use Illuminate\Http\Request;

/** Раздел админки ERP «Производство → Техника» («Доработки ERP», п. 4) */
class Vehicles extends Controller {
	use HasCrudController;

	public const SECTION_ABILITY = 'section-production.vehicles:admin';

	protected $data = [];

	public function __construct(private readonly VehicleRegistry $registry) {
		// Как и страница раздела (get_section): главный администратор — всегда, остальные — с правом на раздел
		$this->middleware(function ($request, $next) {
			abort_unless($request->user('admin')?->can(self::SECTION_ABILITY), 403);

			return $next($request);
		});
	}

	/** Список: используемые сверху, «не используется» — ниже */
	public function index(Request $request) {
		['views' => $viewPath] = $request->validate(['views' => 'string|required']);
		$list = Vehicle::with(['drivers.staff:id,sname,fname,mname,working'])
			->orderBy('not_used')
			->orderBy('name')
			->orderBy('plate')
			->get()
			->map(fn (Vehicle $vehicle) => $this->row($vehicle));

		return $this->view($viewPath.'.list', ['list' => $list, 'itemView' => $viewPath.'.item']);
	}

	public function create(Request $request) {
		['views' => $viewPath, 'newItemIndex' => $index] = $request->validate(['views' => 'string|required', 'newItemIndex' => 'numeric|required']);

		return $this->view($viewPath.'.new', ['index' => $index]);
	}

	public function store_show(Request $request) {
		$valid = $request->validate([
			'views' => 'string|required',
			'name' => 'string|required|max:255',
			'plate' => 'nullable|string|max:40',
			'not_used' => 'nullable|boolean',
		]);
		$vehicle = $this->registry->createVehicle($valid['name'], $valid['plate'] ?? null, (bool) ($valid['not_used'] ?? false));

		return $this->view($valid['views'].'.item', $this->row($vehicle->load('drivers.staff')));
	}

	public function update(Request $request, $id) {
		$valid = $request->validate([
			'name' => 'string|required|max:255',
			'plate' => 'nullable|string|max:40',
			'not_used' => 'nullable|boolean',
		]);
		$this->registry->updateVehicle(Vehicle::findOrFail($id), $valid['name'], $valid['plate'] ?? null, (bool) ($valid['not_used'] ?? false));

		return response()->json(['updated' => true]);
	}

	public function destroy(?int $id = null) {
		$this->registry->deleteVehicle(Vehicle::findOrFail($id));

		return response()->json(['deleted' => true]);
	}

	/** Окно «Водители машины»: весь список водителей ERP с отметкой закреплённых */
	public function drivers(Request $request) {
		['vehicle' => $vehicleId, 'views' => $viewPath] = $request->validate(['vehicle' => 'required|integer', 'views' => 'string|required']);
		$vehicle = Vehicle::findOrFail($vehicleId);
		$assigned = $vehicle->drivers()->pluck('drivers.id')->flip();
		$drivers = Driver::with('staff:id,sname,fname,mname,working')->get()
			->map(fn (Driver $driver) => [
				'id' => $driver->id,
				'name' => ObjectDirectory::name($driver->staff?->sname, $driver->staff?->fname, $driver->staff?->mname),
				'not_used' => (bool) $driver->not_used,
				'fired' => !$driver->staff?->working,
				'assigned' => $assigned->has($driver->id),
			])
			->sortBy('name')
			->values();

		return $this->view($viewPath.'.drivers', compact('vehicle', 'drivers'));
	}

	public function set_driver(Request $request) {
		$valid = $request->validate(['vehicle' => 'required|integer', 'driver' => 'required|integer', 'assigned' => 'required|boolean']);
		$this->registry->setVehicleDriver(Vehicle::findOrFail($valid['vehicle']), Driver::findOrFail($valid['driver']), (bool) $valid['assigned']);

		return response()->json(['assigned' => (bool) $valid['assigned']]);
	}

	public function history(Request $request) {
		['vehicle' => $vehicleId, 'views' => $viewPath] = $request->validate(['vehicle' => 'required|integer', 'views' => 'string|required']);
		Vehicle::findOrFail($vehicleId);

		return $this->view($viewPath.'.history', ['history' => ErpAudit::history(VehicleRegistry::VEHICLE, (int) $vehicleId)]);
	}

	private function row(Vehicle $vehicle): array {
		return [
			'id' => $vehicle->id,
			'name' => $vehicle->name,
			'plate' => $vehicle->plate,
			'not_used' => (bool) $vehicle->not_used,
			'drivers' => $vehicle->drivers
				->map(fn (Driver $driver) => [
					'name' => ObjectDirectory::name($driver->staff?->sname, $driver->staff?->fname, $driver->staff?->mname),
					// Такого водителя приложение не подставит (П-ОБ-23а)
					'unavailable' => $driver->not_used || !$driver->staff?->working,
				])
				->sortBy('name')
				->values()
				->all(),
		];
	}
}
