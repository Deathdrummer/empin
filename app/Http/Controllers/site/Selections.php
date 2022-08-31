<?php namespace App\Http\Controllers\site;

use App\Http\Controllers\Controller;
use App\Models\Selection;
use App\Traits\HasCrudController;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class Selections extends Controller {
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
			'views'		=> $viewPath,
		] = $request->validate([
			'views'		=> 'string|required',
		]);
		if (!$viewPath) return response()->json(['no_view' => true]);
		
		
		$list = Selection::where(['account_id' => auth('site')->user()->id])
			->withCount('contracts')
			->orderBy('_sort', 'ASC')
			->get();
		
		logger('dfgdfgdfgdfgdfg');
		

		$itemView = $viewPath.'.item';
		
		return $this->viewWithLastSortIndex(Selection::class, $viewPath.'.list', compact('list', 'itemView'), '_sort');
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
		if (!$item = $this->_storeRequest($request)) return response()->json(false);
		$viewPath = $request->input('views');
		if (!$viewPath) return response()->json(['no_view' => true]);

		return $this->view($viewPath.'.item', $item);
    }
	
	
	
	private function _storeRequest($request = null) {
		if (!$request) return false;
		
		$validFields = $request->validate([
			'title'	=> 'required|string',
			'_sort'	=> 'required|regex:/[0-9]+/'
		]);
		
		if (!$created = Selection::create($validFields)) return false;
		return $created;
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
				Rule::unique('contracts_selections')->ignore(Selection::where('id', $id)->first()),
			],
			'_sort'					=> 'numeric'
		]);
		
		Selection::where('id', $id)->update($validFields);
		
		return response()->json(true);
    }
	
	
	
	
	
	
	
	
	
	
	
	/**
     * Удаление записи
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(?int $id = null) {
		if (!$id) return response()->json(false);
		$stat = Selection::destroy($id);
		return response()->json($stat);
    }
	
	
	
	
	
	//------------------------------------------------------------------------------
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function add_contract(Request $request) {
		[
			'contractId' 	=> $contractId,
			'selectionId' 	=> $selectionId
		] = $request->validate([
			'contractId' 	=> 'required|numeric',
			'selectionId' 	=> 'required|numeric'
		]);
		
		$selection = Selection::find($selectionId);
		$statData = $selection->contracts()->syncWithoutDetaching($contractId);
		return response()->json($statData);
	}
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function remove_contract(Request $request) {
		[
			'contractId' 	=> $contractId,
			'selectionId' 	=> $selectionId
		] = $request->validate([
			'contractId' 	=> 'required|numeric',
			'selectionId' 	=> 'required|numeric'
		]);
		
		$selection = Selection::find($selectionId);
		$statData = $selection->contracts()->detach($contractId);
		return response()->json($statData);
		
	}
	
	
	
	
	

}