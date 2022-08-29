<?php namespace App\Http\Controllers\crud;

use App\Http\Controllers\Controller;

use App\Traits\HasCrudController;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


class Roles extends Controller {
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
		
		/* 
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
		]); */
		
	}
	
	
	
    /**
     * Вывод всех записей
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
		$validData = $request->validate([
			'views' => 'string|required',
			'guard' => 'string|required'
		]);
		$viewPath = $validData['views'];
		if (!$viewPath) return response()->json(['no_view' => true]);
        
		$list = Role::where('guard_name', $validData['guard'])->orderBy('sort', 'ASC')->get();
		
		$itemView = $viewPath.'.item';
		
		return $this->viewWithLastSortIndex(Role::class, $viewPath.'.list', compact(['list', 'itemView']), 'sort');
    }
	
	
	
    /**
     * Показ формы создания
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request) {
		$viewPath = $request->input('views');
		$newItemIndex = $request->input('newItemIndex');
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
		
		return $this->view($viewPath.'.item', $item);
    }
	
	
	
	private function _storeRequest($request = null) {
		if (!$request) return false;
		
		$validFields = $request->validate([
			'title'	=> 'string|required|unique:roles,title',
			'guard'	=> 'required|string|exclude',
			'sort'	=> 'required|regex:/[0-9]+/'
		]);
		
		$guard = $request->input('guard');
		$validFields['name'] = $guard.'-'.translitSlug($validFields['title']);

		$validFields['guard_name'] = $guard;
		
		return Role::create($validFields);
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
		$validFields = $request->validate([
			'title'	=> [
        		'string',
        		'required',
				Rule::unique('roles')->ignore(Role::where('id', $id)->first()),
			],
			'guard'	=> 'required|string|exclude',
		]);
		
		$guard = $request->input('guard');
		
		$validFields['name'] = $guard.'-'.translitSlug($validFields['title']);
		
		$validFields['guard_name'] = $guard;
       
	    $pdateData = Role::where('id', $id)->update($validFields);
		return response()->json($pdateData);
    }
	
	
	
	
	
    /**
     * Удаление записи
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(?int $id = null) {
		$stat = Role::destroy($id);
		return response()->json($stat);
    }
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function permissions(Request $request) {
		$valid = $request->validate([
			'view'	=> 'required|string',
			'guard'	=> 'required|string',
			'role'	=> 'required|numeric'
		]);
		
		$permissions = Permission::where('guard_name', $valid['guard'])
			->where('group', '!=', null)
			->orderBy('sort', 'ASC')
			->get()
			->groupBy('group');
		
		$rolePermissions = Role::where('id', $valid['role'])->first()->permissions->keyBy('id');
		
		$this->addSettingToGlobalData('permissions_groups', 'id', 'name');
		
		return $this->view($valid['view'], ['permissions' => $permissions, 'role_permissions' => $rolePermissions]);
	}
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function permissions_save(Request $request) {
		$valid = $request->validate([
			'role'			=> 'required|numeric',
			'permission'	=> 'required|numeric',
			'stat'			=> 'required|numeric'
		]);
		
		if (!$role = Role::where('id', $valid['role'])->first()) return response()->json(false);
		
		if ($valid['stat']) {
			if (!$role->hasPermissionTo($valid['permission'])) $role->givePermissionTo($valid['permission']);
		} else {
			if ($role->hasPermissionTo($valid['permission'])) $role->revokePermissionTo($valid['permission']);
		}
		
		return response()->json($valid);
	}
	
	
	
}