<?php namespace App\Http\Controllers\site;

use App\Http\Controllers\Controller;
use App\Models\ContractSelection;
use App\Models\Selection;
use App\Models\SelectionSort;
use App\Services\Business\User as UserService;
use App\Services\Business\Department as DepartmentService;
use App\Traits\HasCrudController;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
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
	protected $user;
	protected $department;
	
	
	public function __construct(UserService $user, DepartmentService $department) {
		$this->user = $user;
		$this->department = $department;
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
		
		$userId = auth('site')->user()->id;
		
		$list = Selection::where(['account_id' => $userId])
			->orWhereJsonContains('subscribed', ['read' => $userId])
			->orWhereJsonContains('subscribed', ['write' => $userId])
			/* ->withCount(['contracts' => function($query) {
				$query->where('archive', 0);
			}]) */
			->withCount('contracts')
			->orderBy(function ($query) {
				$query->select('sort')
					->from('selection_sort')
					->whereColumn('selection_sort.selection_id', 'contracts_selections.id')
					->where('selection_sort.account_id', '=', auth()->id())
					->limit(1);
			})
			->get()
			->map(function($item) use($userId) {
				$item['subscribed_read'] = $item['account_id'] != $userId && in_array($userId, ($item['subscribed']['read'] ?? [])) ? true : false;
				$item['subscribed_write'] = $item['account_id'] != $userId && in_array($userId, ($item['subscribed']['write'] ?? [])) ? true : false;
				$item['subscribed'] = $item['account_id'] != $userId ? true : false;
				return $item;
			});
		
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
		$item['subscribed'] = false;
		return $this->view($viewPath.'.item', $item);
    }
	
	
	
	private function _storeRequest($request = null) {
		if (!$request) return false;
		
		$validFields = $request->validate([
			'title'	=> 'required|string',
			'_sort'	=> 'required|regex:/[0-9]+/'
		]);
		
		if (!$created = Selection::create($validFields)) return false;
		
		$userId = auth('site')->user()->id;
		
		$maxSelectionSort = SelectionSort::where(['account_id' => $userId])->max('sort') ?: 0;
		SelectionSort::create([
			'account_id' 	=> $userId,
			'selection_id'	=> $created->id, 
			'sort' 			=> $maxSelectionSort+1,
		]);
		
		$created['subscribed_read'] = $created['account_id'] != $userId && in_array($userId, ($created['subscribed']['read'] ?? [])) ? true : false;
		$created['subscribed_write'] = $created['account_id'] != $userId && in_array($userId, ($created['subscribed']['write'] ?? [])) ? true : false;
		$created['subscribed'] = $created['account_id'] != $userId ? true : false;
		
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
			'_sort' => 'numeric'
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
	public function add_contracts(Request $request) {
		[
			'contractIds' 	=> $contractIds,
			'selectionId' 	=> $selectionId
		] = $request->validate([
			'contractIds' 	=> 'required|array',
			'selectionId' 	=> 'required|numeric'
		]);
		
		$selection = Selection::find($selectionId);
		$statData = $selection->contracts()->syncWithoutDetaching($contractIds);
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
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function remove_contracts(Request $request) {
		[
			'contractIds' 	=> $contractIds,
			'selectionId' 	=> $selectionId
		] = $request->validate([
			'contractIds' 	=> 'required|array',
			'selectionId' 	=> 'required|numeric'
		]);
		
		$selection = Selection::find($selectionId);
		$statData = $selection->contracts()->detach($contractIds);
		return response()->json($statData);
	}
	
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function users_to_share(Request $request) {
		[
			'views' => $viewsPath,
			'selection_id' => $selectionId,
		] = $request->validate([
			'views'			=> 'string|required',
			'selection_id'	=> 'numeric|required',
			'subscribed'	=> 'exclude',
		]);
		
		$subscribed = request('subscribed') ?: false;
		
		$depsUsers = $this->user->getWithDepartments(false, auth('site')->user()->id);
		
		$departments = $this->department->getAll()->keyBy('id');
		
		return $this->view($viewsPath.'.users', compact('depsUsers', 'departments', 'selectionId', 'subscribed'));
	}
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function share() {
		$type = request('type');
		$unitId = request('unitId');
		$selectionId = request('selectionId');
		$permission = request('permission');
		
		if (!$selectionId || !$unitId || !$type) return response()->json(false);
		
		if (in_array($type, ['clone-user-department', 'subscribe-user-department'])) {
			
			$users = $this->user->getWithDepartments($unitId)->toArray();
			$usersIds = array_column(reset($users), 'id');
			
			if ($type == 'clone-user-department') {
				foreach ($usersIds as $userId) {
					$this->_cloneSelection($selectionId, $userId);
				}
				
			} elseif ($type == 'subscribe-user-department') {
				foreach ($usersIds as $userId) {
					$this->_subscribeSelection($selectionId, $userId, $permission);
				}
			}
			
			return response()->json(true);
		
		} elseif(in_array($type, ['clone-user', 'subscribe-user'])) {
			
			if ($type == 'clone-user') {
				$this->_cloneSelection($selectionId, $unitId);
			} elseif ($type == 'subscribe-user') {
				$this->_subscribeSelection($selectionId, $unitId, $permission);
			}
			
			return response()->json(true);
		}
	}
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	private function _cloneSelection($selectionId, $userId) {
		$row = ContractSelection::find($selectionId);
		$maxSelectionSort = ContractSelection::where('account_id', $userId)->max('_sort') ?: 0;
		
		$created = ContractSelection::create([
			'account_id'	=> $userId,
			'title'			=> $row->title,
			'_sort'			=> $maxSelectionSort + 1,
		]);
		
		$selection = Selection::find($created->id);
		$selection->contracts()->attach($row->contracts->pluck('id'));
		
		return true;
	}
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	private function _subscribeSelection($selectionId, $userId, $permission) {
		$row = ContractSelection::find($selectionId);
		
		$primaryPermission = $permission == 'read' ? 'read' : 'write';
		$secondaryPermission = $permission == 'read' ? 'write' : 'read';
		
		$subscribed = $row->subscribed;
		if (!isset($subscribed[$primaryPermission])) $subscribed[$primaryPermission] = [];
		
		if (!in_array($userId, (array)$subscribed[$primaryPermission])) {
			
			$subscribed[$primaryPermission][] = $userId;
		} 
		
		if (isset($subscribed[$secondaryPermission]) && in_array($userId, (array)$subscribed[$secondaryPermission])) {
			$index = array_search($userId, $subscribed[$secondaryPermission]);
			unset($subscribed[$secondaryPermission][$index]);
			if (empty($subscribed[$secondaryPermission])) unset($subscribed[$secondaryPermission]);
		} 
		
		$row->subscribed = $subscribed;
		$stat = $row->save();
		return $stat;
	}
	
	
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function unsubscribe() {
		$selectionId = request('id');

		$row = ContractSelection::find($selectionId);
		$userId = auth('site')->user()->id;
		
		$subscribed = $row->subscribed;
		
		if (!in_array($userId, $subscribed['read'] ?? []) && !in_array($userId, $subscribed['write'] ?? [])) return true;
		
		if (($key = array_search($userId, $subscribed['read'] ?? [])) !== false) array_splice($subscribed['read'], $key, 1);
		if (($key = array_search($userId, $subscribed['write'] ?? [])) !== false) array_splice($subscribed['write'], $key, 1);
		
		$row->subscribed = (empty($subscribed['read'] ?? []) && empty($subscribed['write'] ?? [])) ? null : $subscribed;
		$stat = $row->save();
		return $stat;
	}
	
	
	
	
	
	
	
	
	
	
	public function add_selection_from_contextmenu(Request $request) {
		if (!$request) return false;
		
		$userId = auth('site')->user()->id;
		
		[
			'title' => $title,
			'contractIds' => $contractIds,
		] = $request->validate([
			'title'	=> 'required|string',
			'contractIds' 	=> 'required|array',
		]);
		
		$validFields['_sort'] = Selection::where('account_id', $userId)->max('_sort') + 1;
		
		if (!$created = Selection::create(compact('title'))) return -1;
		
		$statData = $created->contracts()->syncWithoutDetaching($contractIds);
		return response()->json($statData);
	}
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function archive(Request $request) {
		[
			'id' => $selectionId,
			'command' => $command,
		] = $request->validate([
			'id'		=> 'required|numeric',
			'command' 	=> 'required|string',
		]);
		
		$row = ContractSelection::find($selectionId);
		$row->archive = $command == 'to' ? 1 : ($command == 'from' ? 0 : 1);
		$stat = $row->save();
		return $stat;
	}
	
	
	
	
	
	
	
	
	/** создать таблицу selection_sort с полями: selection_id, account_id, sort 
	* это нужно для того, чтобы сортировать для каждого сотрудника, так как одна и та же подборка может быть более чем у одного сотрудника
	*/

	/**
	 * @param 
	 * @return 
	 */
	public function sort(Request $request) {
		$items = collect($request->get('items'));
		$userId = auth('site')->user()->id;
		$newItems = $items->map(function ($sort,  $selectionId ) use ($userId) {
			return [
				'account_id'	=> $userId,
				'selection_id'	=> $selectionId,
				'sort' 			=> $sort
			];
		})->values();
		
		SelectionSort::upsert(
			$newItems->toArray(),
			['account_id', 'selection_id'],
			['sort']
		);
		
		return response()->json(true);
	}
	

}