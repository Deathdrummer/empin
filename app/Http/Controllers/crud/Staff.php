<?php namespace App\Http\Controllers\crud;

use App\Http\Controllers\Controller;
use App\Mail\UserCreated;
use App\Models\Department;
use App\Models\User;
use App\Traits\HasCrudController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Staff extends Controller {
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
		
		$list = User::with('roles:id')
			->withExists(['roles as hasRoles', 'permissions as hasPermissions'])
			->orderBy('_sort', 'ASC')
			->get();
			
		$this->_addRolesToData();
		$this->_addDepartmentsToData();
		
		$itemView = $viewPath.'.item';
		
		return $this->viewWithLastSortIndex(User::class, $viewPath.'.list', compact(['list', 'itemView']));
    }
	
	

    /**
     * Показ формы создания
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request) {
		$viewPath = $request->input('views');
		$newItemIndex = $request->input('newItemIndex');
		
		$this->_addRolesToData();
		$this->_addDepartmentsToData();
		
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
		
		$this->_addRolesToData();
		$this->_addDepartmentsToData();
		
		return $this->view($viewPath.'.item', $item);
    }
	
	
	
	private function _storeRequest($request = null) {
		if (!$request) return false;
		
		$validFields = $request->validate([
			'email' 		=> 'email|required|unique:users,email',
			'pseudoname'	=> 'string|required|max:50|unique:users,pseudoname',
			'role'			=> 'numeric|nullable|exclude',
			'department_id'	=> 'numeric|nullable',
			'_sort'			=> 'required|regex:/[0-9]+/'
		]);
		
		$role = $request->input('role');
		$validFields['password'] = Str::random(8);
		
		if (!$user = User::create($validFields)) return response()->json(false);
		
		if ($role) $user->assignRole($role);
		
		Mail::to($user->email)->send(new UserCreated($validFields));
		
		return User::with('roles:id')->withExists(['roles as hasRoles', 'permissions as hasPermissions'])->find($user->id);
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
			'role' 			=> 'numeric|nullable',
			'department_id'	=> 'numeric|nullable',
		]);
		
		$user = User::where('id', $id)->first();
		
		$user->department_id = $validated['department_id'];
		$user->save();
		
		if ($validated['role']) {
			$data = $user->syncRoles($validated['role']);
			$user->permissions()->detach();
			return response()->json($data);
		}
		
		return response()->json(true);
    }
	
	
	
    /**
     * Удаление записи
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(?int $id = null) {
		$stat = User::destroy($id);
		return response()->json($stat);
    }
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function send_email(Request $request) {
		if (!$id = $request->input('id')) return response()->json(false);
		if (!$user = User::select('email')->where('id', $id)->first()) return response()->json(false);
		$stat = Mail::to($user->email)->send(new UserCreated($user));
		return response()->json($stat);
	}
	
	
	
	/**
	 * @param integer $group
	 * @return 
	 */
	private function _addRolesToData() {
		$this->data['roles'] = [];
		$this->data['roles_custom'] = [];
		
		$allRoles = Role::select(['id', 'title'])
			->where(['guard_name' => 'site'])
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
	private function _addDepartmentsToData() {
		$this->data['departments'] = [];
		if (!$departments = Department::all()) return false;
		
		$depsData = $departments->mapWithKeys(function ($item, $key) {
			return [$item['id'] => $item['name']];
		})->toArray();
		
		$this->data['departments'] = $depsData;
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
		
		if (!$user = User::find($valid['user'])) return response()->json(false);
		
		$allPermissions = Permission::where('guard_name', $valid['guard'])
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
		
		if (!$user = User::find($valid['user'])) return response()->json(false);
		
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