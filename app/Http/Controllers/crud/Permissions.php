<?php namespace App\Http\Controllers\crud;

use App\Http\Controllers\Controller;
use App\Models\AdminSection;
use App\Models\Section;
use App\Traits\HasCrudController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

class Permissions extends Controller {
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
        
		$list = Permission::where('guard_name', $validData['guard'])
					->whereNot('name', 'like', 'section-%')
					->orderBy('sort', 'ASC')
					->get();
				
		$this->addSettingToGlobalData('permissions_groups', 'id', 'name', 'group:'.$validData['guard']);
		
		$itemView = $viewPath.'.item';
		
		return $this->viewWithLastSortIndex(Permission::class, $viewPath.'.list', compact(['list', 'itemView']), 'sort');
    }
	
	
	
    /**
     * Показ формы создания
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request) {
		$viewPath = $request->input('views');
		$guard = $request->input('guard');
		$newItemIndex = $request->input('newItemIndex');
		if (!$viewPath) return response()->json(['no_view' => true]);
		$this->addSettingToGlobalData('permissions_groups', 'id', 'name', 'group:'.$guard);
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
		$guard = $request->input('guard');
		
		$item = $this->_storeRequest($request);
		
		if (!$viewPath) return response()->json(['no_view' => true]);
		$this->addSettingToGlobalData('permissions_groups', 'id', 'name', 'group:'.$guard);
		return $this->view($viewPath.'.item', $item);
    }
	
	
	
	private function _storeRequest($request = null) {
		if (!$request) return false;
		
		$validFields = $request->validate([
			'name'	=> 'string|required|unique:permissions,name',
			'title'	=> 'string|required|unique:permissions,title',
			'guard'	=> 'required|string|exclude',
			'group'	=> 'required|numeric',
			'sort'	=> 'required|regex:/[0-9]+/'
		]);
		
		$guard = $request->input('guard');
		//$validFields['name'] = $guard.'-'.translitSlug($validFields['title']);

		$validFields['guard_name'] = $guard;
		
		$createdPermission = Permission::create($validFields);
		Artisan::call('optimize:clear');
		return $createdPermission;
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
				Rule::unique('permissions')->ignore(Permission::where('id', $id)->first()),
			],
			'group'	=> 'required|numeric',
			'guard'	=> 'required|string|exclude',
		]);
		
		$guard = $request->input('guard');
		
		//$validFields['name'] = $guard.'-'.translitSlug($validFields['title']);
		
		$validFields['guard_name'] = $guard;
       
	    $updateStat = Permission::where('id', $id)->update($validFields);
		return response()->json($updateStat);
    }
	
	
	
	
	
    /**
     * Удаление записи
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(?int $id = null) {
		$stat = Permission::destroy($id);
		return response()->json($stat);
    }
	
	
	
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function sections(Request $request) {
		$viewPath = $request->input('views');
		$guard = $request->input('guard');
		
		if ($guard == 'admin') {
			$sections = AdminSection::all()
				->sortBy('section')
				->mapWithKeys(function ($item, $key) {
					return [$item['section'] => $item];
				});
		} elseif($guard == 'site') {
			$sections = Section::all()
				->mapWithKeys(function ($item, $key) {
					return [$item['section'] => $item];
				});
		}
		
		$perms = Permission::select('name', 'title', 'group')
			->where('name', 'like', 'section-%')
			->where('guard_name', $guard)
			->get()
			->mapWithKeys(function ($item) use($guard) {
				return [Str::replace(['section-', ':'.$guard], '', $item['name']) => [
					'has_permission' => true,
					'group' => $item['group'],
					'perm_title' => $item['title']]
				];
			});
		
		//$list = $sections->replaceRecursive($perms->toArray())->sortBy('id');
		
		$list = array_replace_recursive($sections->toArray(), $perms->toArray());
		
		$this->addSettingToGlobalData('permissions_groups', 'id', 'name', 'group:'.$guard);
		
		return $this->view($viewPath.'.list', compact('list', 'guard'));
	}
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function section_save(Request $request) {
		$guard = $request->input('guard');
		$sections = $request->collect('sections')->filter(function ($value) {
			return isset($value['group']);
		})->map(function ($item) use($guard) {
			$item['guard_name'] = $guard;
			return $item;
		});
		
		$ids = $sections->keys();
			
		Permission::upsert($sections->all(), ['name'], ['title', 'group']);
		Artisan::call('optimize:clear');
		return $ids;
	}
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function section_remove(Request $request) {
		$group = $request->input('group');
		return Permission::where(['group' => $group])->update(['group' => null]);
	}
	
	
	
	
	
}