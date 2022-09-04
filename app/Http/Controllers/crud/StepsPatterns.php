<?php namespace App\Http\Controllers\crud;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\StepPattern;
use App\Traits\HasCrudController;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class StepsPatterns extends Controller {
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
		[
			'views' 		=> $viewPath,
		] = $request->validate([
			'views'			=> 'string|required',
		]);
		
		if (!$viewPath) return response()->json(['no_view' => true]);
		
		$list = StepPattern::orderBy('_sort', 'ASC')->get();
		
		$this->addSettingToGlobalData('contract-customers:customers', 'id', 'name');
		$this->data['customers'][0] = 'По-умолчанию';
		ksort($this->data['customers']);
		
		$itemView = $viewPath.'.item';
		
		return $this->viewWithLastSortIndex(StepPattern::class, $viewPath.'.list', compact('list', 'itemView'), '_sort');
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
		
		$hasCustomers = StepPattern::select('customer')->get()->pluck('customer')->toArray();

		$this->addSettingToGlobalData('contract-customers:customers', 'id', 'name');
		$this->data['customers'][0] = 'По-умолчанию';
		ksort($this->data['customers']);
		
		$this->data['customers'] = array_filter($this->data['customers'], function ($value, $key) use($hasCustomers) {
			return !in_array($key, $hasCustomers);
		}, ARRAY_FILTER_USE_BOTH);
		
		
		
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
		
		$this->addSettingToGlobalData('contract-customers:customers', 'id', 'name');
		
		return $this->view($viewPath.'.item', $item);
	}
	
	
	
	private function _storeRequest($request = null) {
		if (!$request) return false;
		
		$validFields = $request->validate([
			'customer'		=> 'numeric|required',
			'_sort'			=> 'required|regex:/[0-9]+/'
		]);
		
		return StepPattern::create($validFields);
	}
	
	
	

	/**
	 * Показ определенной записи
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	//public function show(Request $request, $id) {
	//	$viewPath = $request->input('views');
	//	$data = $request->except(['views']);
	//	if (!$viewPath) return response()->json(['no_view' => true]);
	//	return $this->view($viewPath.'.item', $data);
	//}
	
	
	
	

	/**
	 * Показ формы редактирования
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	//public function edit($id) {
	//	logger('edit');
	//}
	
	
	
	

	/**
	 * Обновление ресурса
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, $id) {
		$validFields = $request->validate([
			'customer'		=> 'numeric|required'
		]);
		
		$updateStat = StepPattern::where('id', $id)->update($validFields);
		return response()->json($updateStat);
	}
	
	
	
	
	
	/**
	 * Удаление записи
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(?int $id = null) {
		$stat = StepPattern::destroy($id);
		return response()->json($stat);
	}
	
	
	
	
	
	
	
	
	
	//----------------------------------------------------------------
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function steps(Request $request) {
		$viewPath = $request->query('views');
		$customer = $request->query('customer');
		
		if (!$viewPath) return response()->json(false);
		$departments = Department::with(['steps' => function($query) {
			$query->orderBy('_sort', 'ASC');
		}])->orderBy('_sort', 'ASC')->get();
		
		$stepsdata = StepPattern::select('rules')->where('customer', $customer)->first();
		$stepsdata = $stepsdata['rules'];
		
		return $this->view($viewPath.'.steps', compact('departments', 'stepsdata'));
	}
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function steps_save(Request $request) {
		$stepsData = $request->input('stepsData');
		$customer = $request->input('customer');
		
		$customerRow = StepPattern::where('customer', $customer)->first();
		$customerRow->rules = $stepsData ?: null;
		
		$stat = $customerRow->save();
		return response()->json($stat);
	}
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function steps_remove(Request $request) {
		$customer = $request->input('customer');
		$stat = StepPattern::where('customer', $customer)->delete();
		return response()->json($stat);
	}
	
	
	
	
	
}