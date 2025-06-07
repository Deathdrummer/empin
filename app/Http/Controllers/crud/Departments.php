<?php namespace App\Http\Controllers\crud;

use App\Http\Controllers\Controller;
use App\Models\AdminSection;
use App\Models\Department;
use App\Models\Section;
use App\Models\Step;
use App\Traits\HasCrudController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

class Departments extends Controller {
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
			'views' => 'string|required'
		]);
		$viewPath = $validData['views'];
		if (!$viewPath) return response()->json(['no_view' => true]);
        
		$list = Department::orderBy('_sort', 'ASC')->get();
		
		$itemView = $viewPath.'.item';
		
		return $this->viewWithLastSortIndex(Department::class, $viewPath.'.list', compact(['list', 'itemView']), '_sort');
    }
	
	
	
    /**
     * Показ формы создания
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request) {
		[
			'views' => $viewPath,
			'newItemIndex' => $newItemIndex
		] = $request->validate([
			'views' 		=> 'string|required',
			'newItemIndex'	=> 'numeric|required',
		]);
		
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
			'name'					=> 'string|required|unique:departments,name',
			'assigned_primary'		=> 'numeric|nullable',
			'show_only_assigned'	=> 'boolean|nullable',
			'show_in_timesheet'		=> 'boolean|nullable',
			'sort'					=> 'required|numeric',
			'_sort'					=> 'required|regex:/[0-9]+/'
		]);
		
		return Department::create($validFields);
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
			'name'	=> [
        		'string',
        		'required',
				Rule::unique('departments')->ignore(Department::where('id', $id)->first()),
			],
			'assigned_primary'		=> 'numeric|nullable',
			'show_only_assigned'	=> 'boolean|nullable',
			'show_in_timesheet'		=> 'boolean|nullable',
			'sort'					=> 'numeric'
		]);
		
		Department::where('id', $id)->update($validFields);
		
		return response()->json(true);
    }
	
	
	
	
	
    /**
     * Удаление записи
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(?int $id = null) {
		$stat = Department::destroy($id);
		return response()->json($stat);
    }
	
	
	
}