<?php namespace App\Http\Controllers\crud;

use App\Http\Controllers\Controller;

use App\Models\Department;

use App\Models\Step;
use App\Traits\HasCrudController;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class Steps extends Controller {
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
	 * @param 
	 * @return 
	 */
	public function init(Request $request) {
		[
			'views' => $viewsPath,
		] = $request->validate([
			'views'	=> 'string|required',
		]);
		
		return $this->view($viewsPath.'.init');
	}
	
	
	
	
	
	
	
	
	
    /**
     * Вывод всех записей
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
		[
			'views' 		=> $viewPath,
			'department_id'	=> $deptId
		] = $request->validate([
			'views'			=> 'string|required',
			'department_id'	=> 'numeric|required',
		]);
		
		if (!$viewPath) return response()->json(['no_view' => true]);
        
		$list = Step::where('department_id', $deptId)
					->orderBy('_sort', 'ASC')
					->get();
		
		$itemView = $viewPath.'.item';
		
		return $this->viewWithLastSortIndex(Step::class, $viewPath.'.list', compact(['list', 'itemView']), '_sort');
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
			'name'	=> [
        		'string',
        		'required',
				Rule::unique('steps')->where(function ($query) use($request) {
					return $query->where(['department_id' => $request->department_id]);
				})
			],
			'type'			=> 'numeric|required',
			'department_id' => 'numeric|required',
			'deadline' 		=> 'present',
			'width' 		=> 'numeric|nullable',
			'sort'			=> 'required|present',
			'_sort'			=> 'required|regex:/[0-9]+/'
		]);
		
		return Step::create($validFields);
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
				Rule::unique('steps')->where(function ($query) use($request) {
					return $query->where(['department_id' => $request->department_id]);
				})->ignore(Step::where('id', $id)->first())
			],
			'type'			=> 'numeric|required',
			'department_id' => 'numeric|required',
			'deadline' 		=> 'present',
			'width' 		=> 'numeric|nullable',
			'sort'			=> 'required|present',
		]);
		
	    $updateStat = Step::where('id', $id)->update($validFields);
		return response()->json($updateStat);
    }
	
	
	
	
	
    /**
     * Удаление записи
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(?int $id = null) {
		$stat = Step::destroy($id);
		return response()->json($stat);
    }
	
}