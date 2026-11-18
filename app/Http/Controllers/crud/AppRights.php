<?php namespace App\Http\Controllers\crud;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Erp\Brigade;
use App\Models\Erp\PermissionEvent;
use App\Models\Erp\PermissionSetting;
use App\Models\Staff;
use App\Models\User;
use App\Production\Objects\ObjectDirectory;
use App\Production\Rights\Right;
use App\Production\Rights\RightSets;
use App\Production\Rights\RightSettings;
use App\Production\Support\ProductionException;
use App\Traits\HasCrudController;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Права приложения ЭМПИН-pro в окне прав сотрудника («Пользователи нов.» → «Права», вкладка прав приложения):
 * настройки права (несколько выдач), история прав. Выдача и отзыв — действующим механизмом ERP (UsersNew).
 */
class AppRights extends Controller {
	use HasCrudController;

	public const SECTION_ABILITY = 'section-personal.users_new:admin';

	private const VIEWS = 'admin.section.personal.render.app_rights';

	protected $data = [];

	public function __construct(private readonly RightSettings $settings) {
		$this->middleware(function ($request, $next) {
			abort_unless($request->user('admin')?->can(self::SECTION_ABILITY), 403);

			return $next($request);
		});
	}

	/** Настройки права сотрудника для правки во вкладке */
	public function editor(Request $request) {
		[$user, $right, $permissionId] = $this->target($request);
		$rows = $this->settings->rows($user, $permissionId)->map(fn (PermissionSetting $s) => $s->snapshot())->all();

		[$staff, $brigades] = $this->settings->editorOptions($right, $rows);

		return $this->view(self::VIEWS.'.editor', [
			'user' => (int) $request->input('user'),
			'permissionId' => $permissionId,
			'right' => $right,
			'rows' => $rows ?: [null],
			'staff' => $staff,
			'brigades' => $brigades,
		]);
	}

	public function save(Request $request) {
		[$user, $right, $permissionId] = $this->target($request);
		$rows = $request->validate(['rows' => 'nullable|array'])['rows'] ?? [];
		$this->settings->save($user, $right, $permissionId, $rows);

		return response()->json(['summary' => $this->summary($user, $right, $permissionId)]);
	}

	/** Применить типовой набор (ТЗ, п. 4.2.3): права приложения сотрудника становятся правами набора */
	public function apply(Request $request) {
		$valid = $request->validate(['user' => 'required|integer', 'set' => 'required|integer']);
		$user = User::fromStaff((int) $valid['user'])->first() ?? throw ProductionException::notFound('Учётная запись не найдена');
		$sets = app(RightSets::class);

		return response()->json(['need_settings' => $sets->apply($user, $sets->find((int) $valid['set']))]);
	}

	/** Вкладка прав приложения заново — после применения набора */
	public function tab(Request $request) {
		$staffId = (int) $request->validate(['user' => 'required|integer'])['user'];
		$user = User::fromStaff($staffId)->first() ?? throw ProductionException::notFound('Учётная запись не найдена');
		$tab = $this->settings->tab($user, $user->getAllPermissions()->pluck('id')->all());

		return $this->view(self::VIEWS.'.tab', ['appRights' => $tab['rights'], 'user' => $staffId]);
	}

	/** История прав сотрудника: выдача, отзыв, роль, настройки — кто, когда */
	public function history(Request $request) {
		$staffId = (int) $request->validate(['user' => 'required|integer'])['user'];
		$user = User::fromStaff($staffId)->first() ?? throw ProductionException::notFound('Учётная запись не найдена');

		$events = PermissionEvent::where('user_id', $user->id)->orderByDesc('id')->limit(500)->get();
		$permissions = Permission::whereIn('id', $events->pluck('permission_id')->filter()->unique())->get(['id', 'name', 'title'])->keyBy('id');
		$roles = Role::whereIn('id', $events->pluck('role_id')->filter()->merge($events->pluck('after.roles')->flatten())->filter()->unique())->pluck('title', 'id');
		$admins = AdminUser::whereIn('id', $events->pluck('by_admin_id')->filter()->unique())->get(['id', 'name', 'pseudoname'])
			->mapWithKeys(fn (AdminUser $a) => [$a->id => $a->name ?: $a->pseudoname]);

		$history = $events->map(function (PermissionEvent $e) use ($permissions, $roles, $admins) {
			$permission = $e->permission_id ? $permissions->get($e->permission_id) : null;
			$right = $permission ? Right::fromPermissionName($permission->name) : null;
			$describe = fn (?array $rows) => $right ? collect($rows ?? [])->map(fn ($r) => $this->settings->describe($right, $r))->implode(' | ') : '';

			return [
				'at' => $e->created_at,
				'action' => $e->action,
				'permission' => $permission?->title ?? $permission?->name,
				'role' => $e->role_id ? ($roles[$e->role_id] ?? ($e->after['set'] ?? null)) : null,
				'before' => $e->action === 'settings_changed' ? $describe($e->before['settings'] ?? []) : null,
				'after' => $e->action === 'settings_changed' ? $describe($e->after['settings'] ?? []) : null,
				'admin' => $e->by_admin_id ? ($admins[$e->by_admin_id] ?? null) : null,
			];
		});

		return $this->view(self::VIEWS.'.history', ['history' => $history]);
	}

	/** Строки «что разрешает» по выдачам права — для вкладки */
	public function summary(User $user, Right $right, int $permissionId): array {
		return $this->settings->rows($user, $permissionId)->map(fn (PermissionSetting $s) => $this->settings->describe($right, $s->snapshot()))->all();
	}

	/** @return array{0: User, 1: Right, 2: int} */
	private function target(Request $request): array {
		$valid = $request->validate(['user' => 'required|integer', 'permission' => 'required|integer']);
		$user = User::fromStaff((int) $valid['user'])->first() ?? throw ProductionException::notFound('Учётная запись не найдена');
		$right = RightSettings::rightOf((int) $valid['permission']) ?? throw ProductionException::invalid('Это не право приложения');
		if (!RightSettings::hasSettings($right)) {
			throw ProductionException::invalid('У права нет настроек');
		}

		return [$user, $right, (int) $valid['permission']];
	}
}
