<?php namespace App\Http\Controllers\crud;

use App\Http\Controllers\Controller;
use App\Production\Erp\RightSets;
use App\Production\Erp\RightSettings;
use App\Production\Support\ProductionException;
use App\Traits\HasCrudController;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

/**
 * Раздел админки ERP «Роли → Типовые наборы приложения» (ТЗ, п. 4.2.3; «Доработки ERP», п. 6): наборы прав приложения
 * по должностям и настройки прав в наборе
 */
class AppRightSets extends Controller {
	use HasCrudController;

	public const SECTION_ABILITY = 'section-roles.app_sets:admin';

	private const VIEWS = 'admin.section.roles.render.app_sets';

	protected $data = [];

	public function __construct(private readonly RightSets $sets, private readonly RightSettings $settings) {
		$this->middleware(function ($request, $next) {
			abort_unless($request->user('admin')?->can(self::SECTION_ABILITY), 403);

			return $next($request);
		});
	}

	public function index(Request $request) {
		['views' => $viewPath] = $request->validate(['views' => 'string|required']);
		$list = $this->sets->all()->map(fn (Role $set) => $this->row($set));

		return $this->view($viewPath.'.list', ['list' => $list, 'itemView' => $viewPath.'.item']);
	}

	public function create(Request $request) {
		['views' => $viewPath, 'newItemIndex' => $index] = $request->validate(['views' => 'string|required', 'newItemIndex' => 'numeric|required']);

		return $this->view($viewPath.'.new', ['index' => $index]);
	}

	public function store_show(Request $request) {
		$valid = $request->validate(['views' => 'string|required', 'title' => 'string|required|max:255']);

		return $this->view($valid['views'].'.item', $this->row($this->sets->create($valid['title'])));
	}

	public function update(Request $request, $id) {
		$valid = $request->validate(['title' => 'string|required|max:255']);
		$this->sets->rename($this->sets->find((int) $id), $valid['title']);

		return response()->json(['updated' => true]);
	}

	public function destroy(?int $id = null) {
		$this->sets->find((int) $id)->delete();

		return response()->json(['deleted' => true]);
	}

	/** Окно набора: права приложения галочками, у прав с настройками — настройки набора */
	public function rights(Request $request) {
		$set = $this->sets->find((int) $request->validate(['set' => 'required|integer'])['set']);

		return $this->view(self::VIEWS.'.rights', ['set' => $set, 'rights' => $this->sets->rows($set)]);
	}

	public function set_right(Request $request) {
		$valid = $request->validate(['set' => 'required|integer', 'permission' => 'required|integer', 'granted' => 'required|boolean']);
		$set = $this->sets->find((int) $valid['set']);
		$this->sets->setRight($set, (int) $valid['permission'], (bool) $valid['granted']);

		return response()->json(['brief' => $this->sets->brief($set)]);
	}

	public function editor(Request $request) {
		$valid = $request->validate(['set' => 'required|integer', 'permission' => 'required|integer']);
		$set = $this->sets->find((int) $valid['set']);
		$right = RightSettings::rightOf((int) $valid['permission']) ?? throw ProductionException::invalid('Это не право приложения');
		$rows = $this->sets->settingsRows($set, (int) $valid['permission']);
		[$staff, $brigades] = $this->settings->editorOptions($right, $rows);

		return $this->view('admin.section.personal.render.app_rights.editor', [
			'user' => null,
			'subjectId' => $set->id,
			'saveAction' => 'appSetSettingsSave',
			'permissionId' => (int) $valid['permission'],
			'right' => $right,
			'rows' => $rows ?: [null],
			'staff' => $staff,
			'brigades' => $brigades,
		]);
	}

	public function save_settings(Request $request) {
		$valid = $request->validate(['set' => 'required|integer', 'permission' => 'required|integer', 'rows' => 'nullable|array']);
		$summary = $this->sets->saveSettings($this->sets->find((int) $valid['set']), (int) $valid['permission'], $valid['rows'] ?? []);

		return response()->json(['summary' => $summary]);
	}

	private function row(Role $set): array {
		return ['id' => $set->id, 'title' => $set->title, 'rights' => $this->sets->brief($set)];
	}
}
