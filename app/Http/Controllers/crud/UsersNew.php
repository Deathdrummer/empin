<?php namespace App\Http\Controllers\crud;

use App\Http\Controllers\Controller;
use App\Mail\UserCreated;
use App\Models\Department;
use App\Models\ListUser;
use App\Models\Staff;
use App\Models\Step;
use App\Models\User;
use App\Traits\HasCrudController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UsersNew extends Controller {
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
		
		$list = Staff::select(['id', 'fname', 'sname', 'mname', 'work_post'])
			->with('registred:id,staff_id,department_id')
			->working()
			->withExists('registred as is_registred')
			->get();
		
		$itemView = $viewPath.'.item';
		
		return $this->viewWithLastSortIndex(Staff::class, $viewPath.'.list', compact(['list', 'itemView']));
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
     * Создание ресурса и показ записи
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store_show(Request $request) {
		$viewPath = $request->input('views');

		$item = $this->_storeRequest($request);
		
		if (!$viewPath) return response()->json(['no_view' => true]);
		
		return $this->view($viewPath.'.item', $item);
    }
	
	
	
	private function _storeRequest($request = null) {
		if (!$request) return false;
		
		$validFields = $request->validate([
			'fname' 				=> 'string|required',
			'mname' 				=> 'string|required',
			'sname' 				=> 'string|required',
			'work_post' 			=> 'string|nullable',
			'passport_series'		=> 'numeric|nullable',
			'passport_number'		=> 'numeric|nullable',
			'passport_date'			=> 'date|nullable',
			'passport_from'			=> 'string|nullable',
			'birth_place' 			=> 'string|nullable',
			'post_index' 			=> 'numeric|nullable',
			'registration_address'	=> 'string|nullable'
		]);
		
		if (!$user = Staff::create($validFields)) return response()->json(false);
		return $user;
	}
	
	

    /**
     * Показ определенной записи
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id) {
		$validData = $request->validate([
			'views' 	=> 'string|required',
		]);
		
		$viewPath = $validData['views'];
		$data = $request->except(['views']);
		
		$user = Staff::where('id', $id)
			->with('registred')
			->withExists('registred as is_registred')
			->first();
		
		
		$regUser = User::where('staff_id', $id)
			->with('roles:id')
			->withExists(['roles as hasRoles', 'permissions as hasPermissions'])
			->first();
		
		
		$user->hasRoles = $regUser['hasRoles'] ?? null;
		$user->hasPermissions = $regUser['hasPermissions'] ?? null;
		$user->roles = $regUser['roles'] ?? null;
		
		//toLog($user->toArray());
		
		$user?->registred?->makeVisible('temporary_password');
		
		$this->_addRolesToData();
		$this->_addDepartmentsToData();
		$this->_addDropdownListsToData($regUser?->department_id);
		$this->_addUsersListsToData($id);
		
		
		if (!$viewPath) return response()->json(['no_view' => true]);
		
		return $this->view($viewPath.'.user_card', [...$data, ...$user->toArray()]);
    }
	
	

    /**
     * Показ формы редактирования
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id) {
        $viewPath = $request->input('views');
		$user = Staff::where('id', $id)->first();
		
		if (!$viewPath) return response()->json(['no_view' => true]);
		return $this->view($viewPath.'.new', $user);
    }
	
	

    /**
     * Обновление ресурса
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
		$validFields = $request->validate([
			'fname' 				=> 'string|required',
			'mname' 				=> 'string|required',
			'sname' 				=> 'string|required',
			'work_post' 			=> 'string|nullable',
			'passport_series'		=> 'numeric|nullable',
			'passport_number'		=> 'numeric|nullable',
			'passport_date'			=> 'date|nullable',
			'passport_from'			=> 'string|nullable',
			'birth_place' 			=> 'string|nullable',
			'post_index' 			=> 'numeric|nullable',
			'registration_address'	=> 'string|nullable'
		]);
		
		$user = Staff::findOrFail($id);
		$user->update($validFields);
		return response()->json($user);
    }
	
	
	
    /**
     * Удаление записи
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(?int $id = null) {
		$stat = Staff::where('id', $id)->update(['working' => 0]);
		
		$user = User::where('staff_id', $id)->first();
		if ($user) {
			$user->update([
				'password'            => null,
				'temporary_password'  => null,
				'remember_token'      => null
			]);
		}
		
		return response()->json($stat);
    }
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//----------------------------------------------------------------------------------------------------------- РЕДАКТИРОВАНИЕ И ОТПРАВКА ПОЧТЫ
	
	
	public function get_user_email(Request $request) { 
		[
			'views'		=> $viewPath,
			'user_id'	=> $userId,
		] = $request->validate([
			'views' 	=> 'string|required',
			'user_id' 	=> 'numeric|required'
		]);
		
		if (!$viewPath) return response()->json(['no_view' => true]);
		
		$staff = Staff::find($userId)->with('registred')->first();
		
		return $this->view($viewPath.'.reg_staff_to_user', $staff['registred'], [], ['x-user' => $staff['registred']]);
	}
	
	
	
	
	public function change_user_email(Request $request) {
		[
			'email'		=> $email,
			'user_id'	=> $userId,
		] = $request->validate([
			'email' 	=> 'email|required',
			'user_id' 	=> 'numeric|required'
		]);
		
		$user = User::where('staff_id', $userId)->first();
		$user->forceFill(['email' => $email,])->save();
		
		return response()->json(true);
	}
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function send_email(Request $request) {
		if (!$userId = $request->input('user_id')) return response()->json(false);
		//if (!$user = User::select('email', 'temporary_password')->where('id', $id)->first()) return response()->json(false);
		if (!$user = User::where('staff_id', $userId)->first()) return response()->json(false);
		
		if (isset($user->temporary_password)) {
			$password = Crypt::decryptString($user->temporary_password);
			$user->forceFill([
				'password' => $password,
				'temporary_password' => null
			])->save();
			
			$userData = [
				'email' => $user->email,
				'password' => $password,
			];
			
			$stat = Mail::to($user->email)->send(new UserCreated($userData));
			return response()->json($stat);
		}
		
		
		$stat = Mail::to($user->email)->send(new UserCreated(['email' => $user->email]));
		return response()->json($stat);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//----------------------------------------------------------------------------------------------------------- РЕГИСТРАЦИЯ ПОЛЬЗОВАТЕЛЯ
	
	
	public function get_staff_to_user_form(Request $request) {
		$validData = $request->validate([
			'views' 	=> 'string|required',
			'user_id' 	=> 'numeric|required'
		]);
		
		$viewPath = $validData['views'];
		
		if (!$viewPath) return response()->json(['no_view' => true]);
		return $this->view($viewPath.'.reg_staff_to_user');
	}
	
	
	
	
	public function reg_staff_to_user(Request $request) {
		[
			'email'		=> $email,
			'user_id'	=> $userId,
		] = $request->validate([
			'email' 	=> 'email|required',
			'user_id' 	=> 'numeric|required',
		]);
		
		if (!$user = User::create(['email' => encodeEmail($email), 'staff_id' => $userId])) return response()->json(false);
		$user->forceFill(['temporary_password' => Crypt::encryptString(Str::random(12))])->save();
		
		$user->makeVisible('temporary_password');
		return $user;
	}
	
	
	
	
	public function unreg_user_from_staff(Request $request) {
		[
			'user_id'	=> $userId,
		] = $request->validate([
			'user_id' 	=> 'numeric|required',
		]);
		
		if (!$user = User::where('staff_id', $userId)->first()) return response()->json(false);
		$user->delete();
		return response()->json(true);
	}
	
	
	
	
	
	
	
	
	
	
	
	//---------------------------------------------------------------------------------------- РОЛИ, ПРАВА, ОТДЕЛ
	
	
	
	public function set_role(Request $request) {
		[
			'user_id'	=> $userId,
			'role_id'	=> $roleId,
		] = $request->validate([
			'user_id' 	=> 'numeric|required',
			'role_id' 	=> 'numeric|required',
		]);
		
		if (!$user = User::fromStaff($userId)->first()) return response()->json(false);
		
		
		$data = $user->syncRoles($roleId);
		$user->permissions()->detach();
		
		//$user->assignRole($roleId);
		
		return response()->json(true);
	}
	
	
	
	public function set_department(Request $request) {
		[
			'user_id'		=> $userId,
			'department_id'	=> $departmentId,
		] = $request->validate([
			'user_id' 		=> 'required|numeric',
			'department_id'	=> 'nullable|numeric',
		]);
		
		$user = User::where('staff_id', $userId)->first();
		
		ListUser::where('staff_id', $userId)->delete();
		
		$user->department_id = $departmentId;
		$user->save();
		
		return response()->json(true);
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
		
		if (!$user = User::fromStaff($valid['user'])->first()) return response()->json(false);
		
		$allPermissions = Permission::where('guard_name', $valid['guard'])
			->whereNot('group', null)
			->get()
			->sortBy('sort', SORT_NATURAL)
			->groupBy('group');
		
		$userPermissions = $user->getAllPermissions()->pluck('id')->toArray();
		
		$this->addSettingToGlobalData('permissions_groups:groups', 'id', null, 'group:'.$valid['guard']);
		
		usort($this->data['groups'], function($a, $b) {
			if (!isset($a['sort']) || !isset($b['sort'])) return 0;
			return strnatcmp($a['sort'], $b['sort']);
		});
		
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
		
		if (!$user = User::fromStaff($valid['user'])->first()) return response()->json(false);
		
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
	
	
	
	
	
	
	
	
	
	
	//----------------------------------------------------------------------------------------
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function add_to_list_user(Request $request) {
		$request->validate([
			'staff_id' => 'required|integer|exists:staff,id',
			'list_id' => 'required|integer'
		]);

		$listUser = ListUser::firstOrCreate([
			'staff_id' => $request->staff_id,
			'list_id' => $request->list_id
		]);
		
		return response()->json($listUser->wasRecentlyCreated);
	}
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function remove_from_list_user(Request $request) {
		$request->validate([
			'staff_id' => 'required|integer|exists:staff,id',
			'list_id' => 'required|integer'
		]);

		$deleted = ListUser::where('staff_id', $request->staff_id)
			->where('list_id', $request->list_id)
			->delete();

		return response()->json([
			'deleted' => (bool) $deleted
		]);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//----------------------------------------------------------------------------------------
	
	
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
	private function _addDropdownListsToData($departmentId = null) {
		if (!$departmentId) return false;
		$this->data['dropdown_lists'] = [];
		if (!$data = Step::dropdowns($departmentId)->get()) return false;
		
		$dropdownLists = $data->mapWithKeys(function ($item, $key) {
			return [$item['id'] => $item['name']];
		})->toArray();
		
		$this->data['dropdown_lists'] = $dropdownLists;
	}
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	private function _addUsersListsToData($staffId = null) {
		if (!$staffId) return false;
		$this->data['user_lists'] = [];
		if (!$listsIds = ListUser::Lists($staffId)->get()->pluck('list_id')->toArray()) return false;
		
		$this->data['user_lists'] = $listsIds;
	}
	
	
	
}