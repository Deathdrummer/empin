<?php namespace App\Http\Controllers\crud;

use App\Http\Controllers\Controller;
use App\Models\Erp\Brigade;
use App\Models\Erp\BrigadeMember;
use App\Models\Staff;
use App\Production\Erp\BrigadeRegistry;
use App\Production\Objects\ObjectDirectory;
use App\Production\Support\OrgClock;
use App\Traits\HasCrudController;
use Illuminate\Http\Request;

/**
 * Раздел админки ERP «Персонал → Бригады» («Доработки ERP», п. 3). Правила — в BrigadeRegistry.
 */
class Brigades extends Controller {
	use HasCrudController;

	protected $data = [];

	public const SECTION_ABILITY = 'section-personal.brigades:admin';

	public function __construct(private readonly BrigadeRegistry $registry, private readonly OrgClock $clock) {
		// Как и страница раздела (get_section): главный администратор — всегда, остальные — с правом на раздел
		$this->middleware(function ($request, $next) {
			abort_unless($request->user('admin')?->can(self::SECTION_ABILITY), 403);

			return $next($request);
		});
	}

	/** Список бригад: действующие сверху, расформированные — ниже */
	public function index(Request $request) {
		['views' => $viewPath] = $request->validate(['views' => 'string|required']);
		$this->staffOptions();
		$today = $this->clock->today();

		$counts = BrigadeMember::activeOn($today)->selectRaw('brigade_id, COUNT(*) as total')->groupBy('brigade_id')->pluck('total', 'brigade_id');
		$list = Brigade::with('foreman:id,sname,fname,mname')
			->orderByRaw('disbanded_on IS NOT NULL')
			->orderBy('name')
			->get()
			->map(fn (Brigade $brigade) => $this->row($brigade, (int) ($counts[$brigade->id] ?? 0)));

		return $this->view($viewPath.'.list', ['list' => $list, 'itemView' => $viewPath.'.item']);
	}

	public function create(Request $request) {
		['views' => $viewPath, 'newItemIndex' => $index] = $request->validate(['views' => 'string|required', 'newItemIndex' => 'numeric|required']);
		$this->staffOptions();

		return $this->view($viewPath.'.new', ['index' => $index, 'today' => $this->clock->today()->toDateString()]);
	}

	/** Новая бригада; выбранный бригадир вносится в состав с указанной даты */
	public function store_show(Request $request) {
		$valid = $request->validate([
			'views' => 'string|required',
			'name' => 'string|required|max:255',
			'foreman_staff_id' => 'nullable|integer',
			'foreman_since' => 'nullable|date',
		]);
		$brigade = $this->registry->create($valid['name'], ($valid['foreman_staff_id'] ?? null) ? (int) $valid['foreman_staff_id'] : null, $valid['foreman_since'] ?? null);
		$this->staffOptions();

		return $this->view($valid['views'].'.item', $this->row($brigade->load('foreman'), $brigade->membersOn($this->clock->today())->count()));
	}

	/** Переименование; бригадир и состав меняются в окне «Состав» */
	public function update(Request $request, $id) {
		$valid = $request->validate(['name' => 'string|required|max:255']);
		$this->registry->rename(Brigade::findOrFail($id), $valid['name']);

		return response()->json(['updated' => true]);
	}

	public function destroy(?int $id = null) {
		// Бригада не удаляется — расформировывается: история заданий, отчётов и выходов сохраняется (ТЗ 10.3.2)
		return response()->json(['error' => 'Бригаду нельзя удалить — её можно расформировать'], 422);
	}

	/** Окно состава: текущие и прежние члены с датами */
	public function members(Request $request) {
		['brigade' => $brigadeId, 'views' => $viewPath] = $request->validate(['brigade' => 'required|integer', 'views' => 'string|required']);
		$brigade = Brigade::findOrFail($brigadeId);
		$this->staffOptions();
		$today = $this->clock->today()->toDateString();

		$members = BrigadeMember::where('brigade_id', $brigade->id)
			->with('staff:id,sname,fname,mname,working')
			->orderByRaw('left_on IS NOT NULL')
			->orderBy('joined_on')
			->get()
			->map(fn (BrigadeMember $m) => [
				'id' => $m->id,
				'staff_id' => (int) $m->staff_id,
				'name' => ObjectDirectory::name($m->staff?->sname, $m->staff?->fname, $m->staff?->mname),
				'fired' => !$m->staff?->working,
				'joined_on' => $m->joined_on->toDateString(),
				'left_on' => $m->left_on?->toDateString(),
				'open' => $m->left_on === null,
				'active' => $m->joined_on->toDateString() <= $today && (!$m->left_on || $m->left_on->toDateString() > $today),
				'foreman' => (int) $m->staff_id === (int) $brigade->foreman_staff_id,
			]);
		$targets = Brigade::whereNull('disbanded_on')->whereKeyNot($brigade->id)->orderBy('name')->get(['id', 'name'])
			->map(fn (Brigade $b) => ['value' => $b->id, 'title' => $b->name])->all();
		$foremanName = $brigade->foreman ? ObjectDirectory::name($brigade->foreman->sname, $brigade->foreman->fname, $brigade->foreman->mname) : null;

		return $this->view($viewPath.'.members', compact('brigade', 'members', 'targets', 'today', 'foremanName'));
	}

	public function add_member(Request $request) {
		$valid = $request->validate(['brigade' => 'required|integer', 'staff_id' => 'required|integer', 'joined_on' => 'required|date']);
		$this->registry->addMember(Brigade::findOrFail($valid['brigade']), (int) $valid['staff_id'], $valid['joined_on']);

		return response()->json(['added' => true]);
	}

	/** Исключение (дата выхода) и исправление дат, в том числе задним числом */
	public function update_member(Request $request) {
		$valid = $request->validate(['member' => 'required|integer', 'joined_on' => 'required|date', 'left_on' => 'nullable|date']);
		$this->registry->correctMember(BrigadeMember::findOrFail($valid['member']), $valid['joined_on'], $valid['left_on'] ?? null);

		return response()->json(['updated' => true]);
	}

	public function remove_member(Request $request) {
		$valid = $request->validate(['member' => 'required|integer']);
		$this->registry->removeMember(BrigadeMember::findOrFail($valid['member']));

		return response()->json(['removed' => true]);
	}

	public function transfer_member(Request $request) {
		$valid = $request->validate(['member' => 'required|integer', 'to' => 'required|integer', 'date' => 'required|date']);
		$this->registry->transfer(BrigadeMember::findOrFail($valid['member']), Brigade::findOrFail($valid['to']), $valid['date']);

		return response()->json(['transferred' => true]);
	}

	/** Назначить бригадиром члена бригады или снять бригадира (staff_id пустой) */
	public function set_foreman(Request $request) {
		$valid = $request->validate(['brigade' => 'required|integer', 'staff_id' => 'nullable|integer']);
		$staffId = ($valid['staff_id'] ?? null) ? (int) $valid['staff_id'] : null;
		$this->registry->setForeman(Brigade::findOrFail($valid['brigade']), $staffId);

		return response()->json(['foreman' => $staffId]);
	}

	public function disband(Request $request) {
		$valid = $request->validate(['brigade' => 'required|integer', 'date' => 'required|date']);
		$brigade = Brigade::findOrFail($valid['brigade']);
		$this->registry->disband($brigade, $valid['date']);

		return response()->json(['disbanded' => true, 'tasks_left' => $this->registry->tasksFrom($brigade, $valid['date'])]);
	}

	public function restore(Request $request) {
		$valid = $request->validate(['brigade' => 'required|integer']);
		$this->registry->restore(Brigade::findOrFail($valid['brigade']));

		return response()->json(['restored' => true]);
	}

	public function history(Request $request) {
		['brigade' => $brigadeId, 'views' => $viewPath] = $request->validate(['brigade' => 'required|integer', 'views' => 'string|required']);
		$brigade = Brigade::findOrFail($brigadeId);

		return $this->view($viewPath.'.history', ['brigade' => $brigade, 'history' => $this->registry->history($brigade)]);
	}

	private function row(Brigade $brigade, int $membersCount): array {
		return [
			'id' => $brigade->id,
			'name' => $brigade->name,
			'foreman_staff_id' => $brigade->foreman_staff_id,
			'foreman_name' => $brigade->foreman ? ObjectDirectory::name($brigade->foreman->sname, $brigade->foreman->fname, $brigade->foreman->mname) : null,
			'members_count' => $membersCount,
			'disbanded_on' => $brigade->disbanded_on?->toDateString(),
		];
	}

	/** Работающие сотрудники ERP для выбора бригадира и членов */
	private function staffOptions(): void {
		$this->data['staff'] = Staff::where('working', 1)->orderBy('sname')->orderBy('fname')->get(['id', 'sname', 'fname', 'mname', 'work_post'])
			->map(fn (Staff $s) => ['value' => $s->id, 'title' => ObjectDirectory::name($s->sname, $s->fname, $s->mname).($s->work_post ? ' — '.$s->work_post : '')])
			->all();
	}
}
