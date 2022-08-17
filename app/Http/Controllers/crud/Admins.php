<?php namespace App\Http\Controllers\crud;

use App\Http\Controllers\Controller;
use App\Mail\AdminUserCreated;
use App\Models\AdminUser;
use App\Traits\HasCrudController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Admins extends Controller {
	use HasCrudController;
	
	
	/**
     * Глобальные данные
     * 	добавляются глобальные данные, которые будут доступны во всех записях списка.
     * 	В списке передается через компонент <x-data>
     * 	В новую запись передается напрямую, без компанента <x-data>
     * 	Данная переменная заполняется автоматически в трейте HasCrudController
     * 	Для добавления данных достаточно просто присвоить их переменной $this->data['название'] = значение (можно отдельно написать метод)
     * 	Для добавления данных из настроек вызвать метод из HasCrudController: 
     * 	$this->addSettingToGlobalData('ключ в настройках[:переименовать ключ]', 'значение в качестве ключа', ['значение в качестве значения'], 'поле для фильтрации[:значение]');
     *
     * @var array
     */
	protected $data = [];
	
	
	
	public function __construct() {
		
		$this->middleware('throttle:5,1')->only([
			'send_email'
		]);
		
		$this->middleware('throttle:10,1')->only([
			'store_show',
			'store',
			'update',
			'destroy',
		]);
		
		$this->middleware('lang')->only([
			'index',
			'create',
			'show',
			'store_show',
			'edit',
			'send_email'
		]);
		
	}
	
	
	
	
	
	
	
	
    /**
     * Вывод всех записей
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
    	$validData = $request->validate(['views' => 'string|required']);
		$viewPath = $validData['views'];
		if (!$viewPath) return response()->json(['no_view' => true]);
		
		$list = AdminUser::with('roles:id')
			->withExists(['roles as hasRoles', 'permissions as hasPermissions'])
			->where('id', '!=', Auth::guard('admin')->user()->id)
			->where('is_main_admin', '!=', 1)
			->orderBy('_sort', 'ASC')
			->get();
		
		
		$this->addRolesToData();
		
		$itemView = $viewPath.'.item';
		
		return $this->viewWithLastSortIndex(AdminUser::class, $viewPath.'.list', compact(['list', 'itemView']));
    }
	
	

    /**
     * Показ формы создания
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request) {
		$viewPath = $request->input('views');
		$newItemIndex = $request->input('newItemIndex');
		
		$this->addRolesToData();
		
		if (!$viewPath) return response()->json(['no_view' => true]);
		return $this->view($viewPath.'.new', ['index' => $newItemIndex]);
    }
	
	

    /**
     * Создание ресурса
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
		$item = $this->_storeRequest($request);
		return response()->json($item);
    }
	
	
	
	/**
     * Создание ресурса и показ записи
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store_show(Request $request) {
		$viewPath = $request->input('views');
		
		$item = $this->_storeRequest($request);
		
		if (!$viewPath) return response()->json(['no_view' => true]);
		
		$this->addRolesToData();
		
		return $this->view($viewPath.'.item', $item);
    }
	
	
	
	private function _storeRequest($request = null) {
		if (!$request) return false;
		
		$validFields = $request->validate([
			'email' 		=> 'email|required|unique:admin_users,email',
			'pseudoname'	=> 'string|required|max:50|unique:admin_users,pseudoname',
			'role'			=> 'numeric|nullable|exclude',
			'_sort'			=> 'required|regex:/[0-9]+/'
		]);
		
		$role = $request->input('role');
		$validFields['password'] = Str::random(8);
		
		if (!$adminUser = AdminUser::create($validFields)) return response()->json(false);
		
		if ($role) $adminUser->assignRole($role);
		
		Mail::to($adminUser->email)->send(new AdminUserCreated($validFields));
		
		return AdminUser::with('roles:id')->withExists(['roles as hasRoles', 'permissions as hasPermissions'])->find($adminUser->id);
	}
	
	

    /**
     * Показ определенной записи
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id) {
        $viewPath = $request->input('views');
		$data = $request->except(['views']);
		if (!$viewPath) return response()->json(['no_view' => true]);
		return $this->view($viewPath.'.item', $data);
    }
	
	

    /**
     * Показ формы редактирования
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        logger('edit');
    }
	
	

    /**
     * Обновление ресурса
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
		$validated = $request->validate([
			//'email' 	=> 'email|required'
			'role' 	=> 'numeric|nullable',
		]);
		
		$user = AdminUser::where('id', $id)->first();
		if ($validated['role']) {
				$data = $user->syncRoles($validated['role']);
			$user->permissions()->detach();
			
			return response()->json($data);
		}
		
		return response()->json(false);
		//$pdateData = AdminUser::where('id', $id)->update($validated);
    }
	
	
	
    /**
     * Удаление записи
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(?int $id = null) {
		$stat = AdminUser::destroy($id);
		return response()->json($stat);
    }
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function send_email(Request $request) {
		if (!$id = $request->input('id')) return response()->json(false);
		if (!$user = AdminUser::select('email')->where('id', $id)->first()) return response()->json(false);
		$stat = Mail::to($user->email)->send(new AdminUserCreated($user));
		return response()->json($stat);
	}
	
	
	
	/**
	 * @param integer $group
	 * @return 
	 */
	public function addRolesToData() {
		$this->data['roles'] = [];
		$this->data['roles_custom'] = [];
		
		$allRoles = Role::select(['id', 'title'])
			->where(['guard_name' => 'admin'])
			->whereNot('title', null)->get()
			->mapWithKeys(function ($item, $key) {
				$newItem[$key] = [
					'value' => $item['id'],
					'title' => $item['title']
				];
				return $newItem;
			})->toArray();
		
		if ($allRoles) $this->data['roles'] = $allRoles;
		
		array_unshift($allRoles, [
			'value' => '',
			'title' => 'Кастомная роль',
			'disabled' => true
		]);
		
		$this->data['roles_custom'] = $allRoles;
	}
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function permissions(Request $request) {
		$valid = $request->validate([
			'view'	=> 'required|string',
			'user'	=> 'required|numeric',
			'guard'	=> 'required|string',
		]);
		
		if (!$user = AdminUser::find($valid['user'])) return response()->json(false);
		
		$allPermissions = Permission::where('guard_name', $valid['guard'])
			->whereNot('group', null)
			->orderBy('sort', 'ASC')
			->get()
			->groupBy('group');
			
		$userPermissions = $user->getAllPermissions()->groupBy('id');
		
		$this->addSettingToGlobalData('permissions_groups', 'id', 'name');
		
		return $this->view($valid['view'], ['permissions' => $allPermissions, 'user_permissions' => $userPermissions, 'user' => $valid['user']]);
	}
	
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function set_permissions(Request $request) {
		$valid = $request->validate([
			'user'			=> 'required|numeric',
			'permission'	=> 'required|numeric',
			'stat'			=> 'required|numeric'
		]);
		
		if (!$user = AdminUser::find($valid['user'])) return response()->json(false);
		
		if ($hasRole = !!count($user->roles)) {
			$rolesPpermissions = $user->getPermissionsViaRoles();
			$user->syncRoles();
			
			$user->givePermissionTo($rolesPpermissions);
		}
		
		if ($valid['stat']) {
			$user->givePermissionTo($valid['permission']);
		} else {
			$user->revokePermissionTo($valid['permission']);
		}
		$hasPermissions = !!count($user->getAllPermissions());
		return response()->json(['was_role' => $hasRole, 'has_permissions' => $hasPermissions]);
	}
	
	
	
}