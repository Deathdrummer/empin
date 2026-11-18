<?php namespace App\Http\Controllers\crud;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Erp\Brigade;
use App\Models\Erp\WorkType;
use App\Models\Staff;
use App\Production\Erp\ObjectProductionRegistry;
use App\Production\Objects\ObjectDirectory;
use App\Traits\HasCrudController;
use Illuminate\Http\Request;

/**
 * Окна «Производство» и «План объекта» из строки договора в админке ERP («Доработки ERP», пп. 1 и 5).
 * Форма договора и её сохранение не меняются. Правила — в ObjectProductionRegistry.
 */
class ContractProduction extends Controller {
	use HasCrudController;

	/** Доступ — как к странице «Договоры» админки (на проде пункт `contracts.contracts` в группе «Настройки») */
	public const SECTION_ABILITY = 'section-contracts.contracts:admin';

	private const VIEWS = 'admin.section.contracts.render.production';

	protected $data = [];

	public function __construct(private readonly ObjectProductionRegistry $registry) {
		$this->middleware(function ($request, $next) {
			abort_unless($request->user('admin')?->can(self::SECTION_ABILITY), 403);

			return $next($request);
		});
	}

	/** Окно «Производство»: ответственный, закреплённые бригады, готовность объекта и её история */
	public function info(Request $request) {
		$contract = Contract::findOrFail($request->validate(['contract' => 'required|integer'])['contract']);
		$info = $this->registry->info($contract);

		$staff = Staff::where('working', 1)
			->when($info['responsible_staff_id'], fn ($q, $id) => $q->orWhere('id', $id))
			->orderBy('sname')->orderBy('fname')->get(['id', 'sname', 'fname', 'mname', 'work_post', 'working'])
			->map(fn (Staff $s) => [
				'value' => $s->id,
				'title' => ObjectDirectory::name($s->sname, $s->fname, $s->mname).($s->work_post ? ' — '.$s->work_post : '').(!$s->working ? ' (уволен)' : ''),
			])->all();
		$brigades = Brigade::where(fn ($q) => $q->whereNull('disbanded_on')->orWhereIn('id', $info['brigade_ids']))
			->with('foreman:id,sname,fname,mname')
			->orderByRaw('disbanded_on IS NOT NULL')->orderBy('name')->get()
			->map(fn (Brigade $b) => [
				'id' => $b->id,
				'name' => $b->name,
				'foreman' => $b->foreman ? ObjectDirectory::name($b->foreman->sname, $b->foreman->fname, $b->foreman->mname) : null,
				'disbanded' => $b->disbanded_on !== null,
				'checked' => in_array($b->id, $info['brigade_ids'], true),
			]);

		$readinessHistory = $this->registry->readinessHistory($contract);

		return $this->view(self::VIEWS.'.info', compact('contract', 'info', 'staff', 'brigades', 'readinessHistory'));
	}

	public function save_info(Request $request) {
		$valid = $request->validate([
			'contract' => 'required|integer',
			'responsible_staff_id' => 'nullable|integer',
			'brigade_ids' => 'nullable|array',
			'brigade_ids.*' => 'integer',
			'readiness' => 'nullable',
			'readiness_shown' => 'nullable',
		]);
		$this->registry->saveInfo(
			Contract::findOrFail($valid['contract']),
			($valid['responsible_staff_id'] ?? null) ? (int) $valid['responsible_staff_id'] : null,
			$valid['brigade_ids'] ?? [],
			$valid['readiness'] ?? null,
			$valid['readiness_shown'] ?? null,
		);

		return response()->json(['saved' => true]);
	}

	/** Окно «План объекта»: действующая редакция для правки и прежние редакции */
	public function plan(Request $request) {
		$contract = Contract::findOrFail($request->validate(['contract' => 'required|integer'])['contract']);
		$revisions = $this->registry->revisions($contract);
		$current = $revisions[0] ?? null;
		$workTypes = WorkType::orderBy('name')->get(['id', 'name', 'unit'])
			->map(fn (WorkType $t) => ['value' => $t->id, 'title' => $t->name.' ('.$t->unit.')', 'unit' => $t->unit])->all();

		return $this->view(self::VIEWS.'.plan', compact('contract', 'revisions', 'current', 'workTypes'));
	}

	public function save_plan(Request $request) {
		$valid = $request->validate([
			'contract' => 'required|integer',
			'items' => 'nullable|array',
			'items.*.work_type_id' => 'nullable|integer',
			'items.*.quantity' => 'nullable',
		]);
		$revision = $this->registry->revisePlan(Contract::findOrFail($valid['contract']), $valid['items'] ?? []);

		return response()->json(['revision_id' => $revision?->id, 'changed' => $revision !== null]);
	}
}
