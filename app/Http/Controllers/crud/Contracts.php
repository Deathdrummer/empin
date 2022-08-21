<?php namespace App\Http\Controllers\crud;

use App\Http\Controllers\Controller;

use App\Models\Contract;
use App\Models\ContractData;
use App\Models\Department;
use App\Models\User;
use App\Traits\HasCrudController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class Contracts extends Controller {
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
			'views'		=> $viewPath,
		] = $request->validate([
			'views'		=> 'string|required',
			'archive'	=> 'numeric|nullable',
		]);
		
		$archiveFilter = $request->input('archive');
		
		if (!$viewPath) return response()->json(['no_view' => true]);
		
		$list = Contract::where(function($query) use($archiveFilter) {
				if (!is_null($archiveFilter)) $query->where('archive', $archiveFilter);
			})
			->orderBy('_sort', 'ASC')
			->get();
			
		$this->_buildDataFromSettings();
		
		$itemView = $viewPath.'.item';
		
		return $this->viewWithLastSortIndex(Contract::class, $viewPath.'.list', compact('list', 'itemView'), '_sort');
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
		
		$this->_buildDataFromSettings();
		
		$departments = Department::with(['steps' => function($query) {
			$query->orderBy('_sort', 'ASC');
		}])->orderBy('_sort', 'ASC')->get();
		
		$this->_addDepsUsersToData($departments);
		
		if (!$viewPath) return response()->json(['no_view' => true]);
        return $this->view($viewPath.'.form', ['index' => $newItemIndex, 'departments' => $departments]);
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
		$this->_buildDataFromSettings();
		return $this->view($viewPath.'.item', $item);
    }
	
	
	
	private function _storeRequest($request = null) {
		if (!$request) return false;
		
		$validFields = $request->validate([
			'title' 			=> 'required|string',
			'titul' 			=> 'required|string',
			'contract' 			=> 'required|string',
			'price' 			=> 'required|numeric',
			'date_start' 		=> 'required|date_format:d-m-Y',
			'date_end' 			=> 'required|date_format:d-m-Y',
			'customer' 			=> 'required|numeric',
			'locality' 			=> 'required|numeric',
			'contractor' 		=> 'required|numeric',
			'type' 				=> 'required|numeric',
			'subcontracting'	=> 'boolean|nullable',
			'hoz_method' 		=> 'boolean|nullable',
			'departments' 		=> 'array|exclude',
			'_sort'				=> 'exclude|regex:/[0-9]+/'
		]);
		
		
		if (!isset($validFields['_sort'])) {
			$maxSort = Contract::max('_sort');
			$validFields['_sort'] = $maxSort + 1;
		}
		
		if (!$createdContract = Contract::create($validFields)) return false;
		
		$contractDepartments = $this->_buildDepartments($request, $createdContract['id']);
		
		// формирование массива для обновления\вставки ответственных
		$assignedUsers = $this->_buildAssignedUsers($request, $createdContract['id']);
		
		// обновление ответственных в таблице contract_data
		if ($assignedUsers) {
			//foreach ($assignedUsers as $row) {
				ContractData::insert($assignedUsers);
			//}
		}
		
		$createdContract->departments()->attach($contractDepartments);
		
		return $createdContract;
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
    public function edit(Request $request, $id) {
		[
			'views' => $viewPath
		] = $request->validate([
			'views' => 'string|required'
		]);
		
		if (!$viewPath) return response()->json(['no_view' => true]);
		
		$contractData = Contract::find($id);
		
		$contrDeps = [];
		if ($contractDepartments = $contractData->departments->toArray()) {
			foreach ($contractDepartments as $item) foreach ($item['pivot']['steps'] as $step) {
				$contrDeps[$item['pivot']['department_id']]['show'] = $item['pivot']['show'];
				//$contrDeps[$item['pivot']['department_id']]['assigned'] = $item['pivot']['assigned'];
				$contrDeps[$item['pivot']['department_id']]['steps'][$step['step_id']] = [
					'show' => true,
					'deadline' => $step['deadline'] ?? null,
				];
			}
		}
		
		$departments = Department::with(['steps' => function($query) {
			$query->orderBy('_sort', 'ASC');
		}])->orderBy('_sort', 'ASC')->get();
		
		
		
		$depsAssignedUsers = ContractData::select(['department_id', 'data'])
			->where(['contract_id' => $id, 'type' => 3])
			->get()
			->mapWithKeys(function ($item, $key) {
				return [$item['department_id'] => (int)$item['data']];
			});
		
		
		$this->_buildDataFromSettings();
		
		$this->_addDepsUsersToData($departments);
		
        return $this->view($viewPath.'.form', [
			'departments' 			=> $departments,
			'deps_assigned_users'	=> $depsAssignedUsers,
			'cd' 					=> $contrDeps
		], $contractData->toArray());
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
			'title' 			=> 'required|string',
			'titul' 			=> 'required|string',
			'contract' 			=> 'required|string',
			'price' 			=> 'required|numeric',
			'date_start' 		=> 'required|date_format:d-m-Y',
			'date_end' 			=> 'required|date_format:d-m-Y',
			'customer' 			=> 'required|numeric',
			'locality' 			=> 'required|numeric',
			'contractor' 		=> 'required|numeric',
			'type' 				=> 'required|numeric',
			'subcontracting'	=> 'boolean|nullable',
			'hoz_method' 		=> 'boolean|nullable',
			'departments' 		=> 'array|exclude',
			'views'				=> 'string|required|exclude'
		]);
		
		
		$contract = Contract::find($id);
		$contract->fill($validFields);
		$contract->save();
		
		$tableShowDepsData = $contract->departments()->get()->mapWithKeys(function($item) {
			return [$item['id'] => !!$item['pivot']['show']];
		})->toArray();
		
		$contractDepartments = $this->_buildDepartments($request, $id, $tableShowDepsData);
		
		$assignedUsers = $this->_buildAssignedUsers($request, $id); // формирование массива для обновления\вставки ответственных
		
		// обновление ответственных в таблице contract_data
		if ($assignedUsers) {
			foreach ($assignedUsers as $row) {
				if (is_null($row['data'])) {
					ContractData::where([
						'contract_id' => $row['contract_id'],
						'department_id' => $row['department_id'],
						'step_id' => $row['step_id']
						])->delete();
				} else {
						ContractData::updateOrCreate([
							'contract_id' => $row['contract_id'],
							'department_id' => $row['department_id'],
							'step_id' => $row['step_id']
							], // по ним ищет
							['data' => $row['data'], 'type' => $row['type']],  // обновляется или создает новую запись по всем переданныым данным с обоих массивов
					);
				}
			}
		}
		
		$contract->departments()->sync($contractDepartments);
		$viewPath = $request->input('views');
		$this->_buildDataFromSettings();
		
		// так как refresh не работает, пришлось повторно получить модель
		// $contract = Contract::withExists('departments as has_departments')->find($id);
		
		return $this->view($viewPath.'.item', $contract);
    }
	
	
	
	
	
    /**
     * Удаление записи
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(?int $id = null) {
		if (!$id) return response()->json(false);
		$stat = Contract::destroy($id);
		return response()->json($stat);
    }
	
	
	
	
	
	/**
	 * @param  Request $request
	 * @param  int  $id 
	 * @return 
	 */
	public function to_archive(Request $request, ?int $id = null) {
		$checked = (int)$request->get('checked');
		if (!$id) return response()->json(false);
		$contract = Contract::find($id);
		$contract->archive = $checked;
		$stat = $contract->save();
		return response()->json($stat);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//---------------------------------------------------------------------------------------
	
	
	
	
	
	/**
	 * @return void
	 */
	private function _buildDataFromSettings() {
		$this->addSettingToGlobalData([[
			'setting'	=> 'contract-customers:customers',
			'key'		=> 'id',
			'value'		=> 'name'
		], [
			'setting'	=> 'contract-types:types',
			'key'		=> 'id',
			'value'		=> 'title'
		], [
			'setting'	=> 'contract-contractors:contractors',
			'key'		=> 'id',
			'value'		=> 'name'
		], [
			'setting'	=> 'contract-locality:locality',
			'key'		=> 'id',
			'value'		=> 'name'
		]]);
		
		$this->data['rand_id'] = Str::random(20);
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	/**
	 * @param Illuminate\Http\Request  $request
	 * @param int  $contractId
	 * @return mixed
	 */
	private function _buildDepartments(Request $request = null, int $contractId = null, $tableShowDepsData = null): mixed {
		if (!$departmentsData = $request->input('departments')) return [];
		foreach ($departmentsData as $dId => $department) {
			if (!$department) {
				unset($departmentsData[$dId]);
				continue;
			} 
			foreach($department['steps'] as $sId => $step) {
				if (!$step) {
					unset($departmentsData[$dId]['steps'][$sId]);
					continue;
				} 
				if (!$step['choosed']) unset($departmentsData[$dId]['steps'][$sId]);
			}
			
			if (empty($departmentsData[$dId]['steps'])) unset($departmentsData[$dId]);
		}
		
		if (empty($departmentsData)) return [];
		
		$depsData = []; $depsAssignedUsers = [];
		foreach ($departmentsData as $deptId => $department) {
			
			$depsSteps = [];
			foreach ($department['steps'] as $stepId => $step) {
				$depsSteps[] = [
					'step_id'	=> $stepId,
					'deadline'	=> $step['deadline'] ?? null,
				];
			}
			
			$show = $department['show'] ? 1 : 0;
			$depsData[$deptId] = [
				'steps'	=> json_encode($depsSteps, JSON_UNESCAPED_UNICODE),
				'show'	=> $show
			];
			
			if ($show && !$tableShowDepsData[$deptId]) $depsData[$deptId]['updated_show'] = now()->setTime(0, 0, 0);
			elseif (!$show) $depsData[$deptId]['updated_show'] = null;
		}
		
		return $depsData;
	}
	
	
	
	
	
	
	
	/**
	 * @param Illuminate\Http\Request  $request
	 * @param int  $contractId
	 * @return mixed
	 */
	private function _buildAssignedUsers(Request $request = null, int $contractId = null): mixed {
		if (!$data = $request->input('assigned')) return false;
		
		
		if (empty($data)) return false;
		foreach ($data as $deptId => $steps) foreach ($steps as $stepId => $assignedId) {
			$depsAssignedUsers[] = [
				 'contract_id' 		=> $contractId,
				 'department_id'	=> (int)str_replace('dep_', '', $deptId) ?? null,
				 'step_id' 			=> (int)str_replace('step_', '', $stepId) ?? null,
				 'type' 			=> 3,
				 'data' 			=> (int)$assignedId ?: null
			];
		}
		
		return $depsAssignedUsers;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	/**
	 * Добавить сотрудников отделов для выпадающего списка
	 * @param 
	 * @return 
	 */
	private function _addDepsUsersToData($departments) {
		$this->data['deps_users'] = [];
		
		$depsIds = $departments->map(function ($item) {
			if ($item['steps']->where('type', 3)->count()) return $item['id'];
		})->whereNotNull()->values();
		
		$depsUsers = User::whereIn('department_id', $depsIds->toArray())
			->get()
			->mapToGroups(function ($item) {
				return [$item['department_id'] => ['value' => $item['id'], 'title' => $item['pseudoname']]];
			});
		
		$this->data['deps_users'] = $depsUsers->toArray();
	}
	
	
	
	
	
	
	
}