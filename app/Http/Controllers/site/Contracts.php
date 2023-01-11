<?php namespace App\Http\Controllers\site;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractChat;
use App\Models\ContractData;
use App\Models\ContractDepartment;
use App\Models\ContractInfo;
use App\Models\Department;
use App\Models\Selection;
use App\Models\Step;
use App\Services\Business\Department as DepartmentService;
use App\Services\Business\Contract as ContractService;
use App\Services\Business\User as UserService;
use App\Traits\Renderable;
use App\Traits\Settingable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class Contracts extends Controller {
	use Renderable, Settingable;
	
	protected $renderPath = 'site.section.contracts.render';
	protected $data = [];
	protected $department;
	protected $user;
	//protected $contracts;
	
	
	public function __construct(DepartmentService $department, ContractService $contract, UserService $user) {
		$this->department = $department;
		$this->contract = $contract;
		$this->user = $user;
	}
	
	
	
	
	
	/**
	 * Данные для формирования списка договоров со всеми данными
	 * 1. список договоров
	 * 2. список отделов
	 * 3. список договоров отдела 
	 * 4. данные по отделам и этапам
	 * 
	 * 
	 * @param 
	 * @return 
	 */
	public function list(Request $request) {
		$list = $this->contract->getWithDepartments($request);
		
		$headers = [];
		$selectionId = null;
		
		if ($request->has('selection')) {
			$selectioned = $request->has('selection');
			$selectionId = $request->get('selection', null);
		}
		
		$headers = [
			'x-count-contracts-current' => $list ? $list->count() : null,
			'x-contracts-ids' 			=> $list ? $list->pluck('id') : null
		];
		
		
		//  Если поиск или подборка - то сформировать количество результатов
		if ($request->get('offset') == 0) {
			$counts = $this->contract->getCounts($request);
			
			$headers['x-count-contracts-all'] = $counts['all'] ?? null;
			$headers['x-count-contracts-departments'] = json_encode($counts['departments']) ?? null;
			$headers['x-count-contracts-archive'] = $counts['archive'] ?? null;
		}
		
		
		
		$columnFilter = null;
		if ($filterRequest = json_decode($request->get('filter', null), true)) {
			$columnFilter = $filterRequest['column'] ?? null;
		}
		
		if (!$list || $list->isEmpty()) return $this->renderWithHeaders('list', compact('columnFilter'), $headers);
		
		$alldeps = $this->department->getWithSteps($request);
		
		
		$this->_addDepsUsersToData($alldeps);
		
		$contractdata = $this->contract->buildData($list->keys());
		$userColums = $this->contract->getUserColums();
		
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
				'setting'	=> 'contract-list-titles-row-height:rowHeight',
				'key'		=> ':single',
			], [
				'setting'	=> 'contract-list-widths:listWidth'
			]
		]);
		
		
		$canEditAll = auth('site')->user()->can('dostup-ko-vsem-otdelam:site');
		$isArchive = $request->has('archive') && $request->get('archive') == 1;
		$isDepartment = $request->has('department_id');
		$departmentId = $request->get('department_id');
		
		$edited = ($canEditAll && !$isArchive) || $isDepartment;
		$searched = $request->has('search') && $request->get('search');
		
		
		$sortField = $request->get('sort_field', 'id');
		$sortOrder = $request->get('sort_order', 'asc');
		
		$selectionEdited = $request->has('edit_selection') && $request->get('edit_selection');
		
		$append = $request->has('append') && $request->get('append');
		
		$allSelections = $searched ? Selection::toChoose()->get()->mapWithKeys(function($item) {
				return [$item['id'] => $item['title']];
			})->toArray() : null;
		
		$user = auth('site')->user();
		$rules = ($searched ? '1' : '0');
		$rules .= ','.($selectionEdited ? '1' : '0');
		$rules .= ','.($isArchive ? '1' : '0');
		$rules .= ','.($user->can('contract-col-to-archive:site') ? '1' : '0'); // отправка договора в архив
		$rules .= ','.($user->can('contract-col-sending:site') ? '1' : '0'); // отправка договора в другой отдел из отдела
		$rules .= ','.($user->can('contract-col-sending-all:site') ? '1' : '0'); // отправка договора в другой отдел из общего списка
		$rules .= ','.($user->can('contract-col-hiding:site') ? '1' : '0'); // скрыть договор
		$rules .= ','.($user->can('contract-col-chat:site') ? '1' : '0'); // просмотр чата
		$rules .= ','.($user->can('contract-col-chat-can-sending:site') ? '1' : '0'); // возможность отправлять сообщения в чате
		$rules .= ','.($user->can('contract-col-return-to-work:site') ? '1' : '0'); // вернуть договор в работу из архива
		
		
		return $this->renderWithHeaders(
			'list',
			compact(
				'list',
				'alldeps',
				'contractdata',
				'edited',
				'rules',
				'departmentId',
				'isArchive',
				'sortField',
				'sortOrder',
				'searched',
				'allSelections',
				'selectionEdited',
				'selectionId',
				'userColums',
				'columnFilter',
				'append'
			),
			$headers
		);
	}
	
	
	
	
	
	
	
	
	/**
	 * Получить только количества договоров для всех разделов
	 * 
	 * @param 
	 * @return 
	 */
	public function counts(Request $request) {
		$counts = $this->contract->getCounts($request);
		$counts = [
			'x-count-contracts-all' 		=> $counts['all'] ?? null,
			'x-count-contracts-departments' => json_encode($counts['departments']) ?? null,
			'x-count-contracts-archive' 	=> $counts['archive'] ?? null,
		];
		return response()->json($counts);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/**
	 * @param Request  $request
	 * @return 
	 */
	public function departments(Request $request) {
		$departments = $this->department->getToSend($request);
		if (request('_responsetype') == 'json') return response()->json($departments);
		return $this->render('departments', compact('departments'));
	}
	
	
	
	
	
	
	
	/** Вернуть уже заданные подборки
	 * @param 
	 * @return 
	 */
	public function selections_to_choose() {
		$contractIds = request('contractIds');
		if (!$contractIds) return response()->json(false);
		
		$contractId = count($contractIds) == 1 ? reset($contractIds) : null;
		$selectionsToChoose = $this->contract->getSelectionsToChoose($contractId);
		
		return response()->json($selectionsToChoose);
	}
	
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function set_data(Request $request) {
		[
			'contractId' 	=> $contractId,
			'departmentId' 	=> $departmentId,
			'stepId' 		=> $stepId,
			'type' 			=> $type,
			'value' 		=> $value,
		] = $request->validate([
			'contractId' 	=> 'required|numeric',
			'departmentId' 	=> 'required|numeric',
			'stepId' 		=> 'required|numeric',
			'type' 			=> 'required|numeric',
			'value' 		=> 'nullable',
		]);
		
		
		$result = ContractData::updateOrCreate(
			['contract_id' => $contractId, 'department_id' => $departmentId, 'step_id' => $stepId], // по ним ищет
			['data' => $value, 'type' => $type],  // обновляется или создает новую запись по всем переданныым данным с обоих массивов
		);
		
		//logger($result);
		return response()->json($result);
	}
	
	
	
	
	
	
	
	
	/**
	 * @param  Request $request
	 * @param  int  $id 
	 * @return 
	 */
	public function hide(Request $request) {
		[
			'contractIds' 	=> $contractIds,
			'departmentId' 	=> $departmentId
		] = $request->validate([
			'contractIds' 	=> 'required|array',
			'departmentId' 	=> 'required|numeric'
		]);
		
		
		$dept = Department::find($departmentId);
		
		$dataToUpdate = [];
		foreach ($contractIds as $countractId) {
			$dataToUpdate[$countractId] = ['hide' => 1];
		}
		
		
		$statData = $dept->contracts()->syncWithoutDetaching($dataToUpdate);
		
		return response()->json($statData['updated']);
		
		
		//$stat = Contract::whereIn('id', $contractIds)->update(['archive' => 1]);
		//return response()->json($stat > 0);
		
		//$contract = Contract::find(reset($contractIds));
		//$statData = $contract->departments()->syncWithoutDetaching([$departmentId => ['hide' => 1]]);

		//return response()->json($statData['updated']);
	}
	
	
	
	
	
	
	
	/**
	 * @param  Request $request
	 * @param  int  $id 
	 * @return 
	 */
	public function to_archive(Request $request) {
		['contractIds' => $contractIds] = $request->validate(['contractIds' => 'required|array']);
		$stat = Contract::whereIn('id', $contractIds)->update(['archive' => 1]);
		return response()->json($stat > 0);
	}
	
	
	
	
	
	/**
	 * @param  Request $request
	 * @param  int  $id 
	 * @return 
	 */
	public function to_work(Request $request) {
		['contractIds' => $contractIds] = $request->validate(['contractIds' => 'required|array']);
		$stat = Contract::whereIn('id', $contractIds)->update(['archive' => 0]);
		return response()->json($stat > 0);
	}
	
	
	
	
	
	
	/**
	 * @param  Request $request
	 * @param  int  $id 
	 * @return 
	 */
	public function send(Request $request) {
		[
			'contractIds' 	=> $contractIds,
			'departmentId' 	=> $departmentId
		] = $request->validate([
			'contractIds' 	=> 'required|array',
			'departmentId' 	=> 'required|numeric'
		]);
		
		$dept = Department::find($departmentId);
		$hasDepsContractIds = $dept->contracts()->wherePivotNotNull('steps')->wherePivot('show', 0)->get()->pluck('id');
		
		$dataToUpdate = [];
		foreach ($contractIds as $countractId) {
			if (!$hasDepsContractIds->contains($countractId)) continue;
			$dataToUpdate[$countractId] = ['show' => 1, 'updated_show' => now()->setTime(0, 0, 0)];
		}
		
		$statData = $dept->contracts()->syncWithoutDetaching($dataToUpdate);
		return response()->json($statData['updated']);
	}
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function statuses(Request $request) {
		if (!auth('site')->user()->can('force-set-contract-color:site')) return response()->json(false);
		$this->addSettingToGlobalData('contracts-deadlines:deadlineStatuses');
		return $this->render('deadline_statuses', $request->all());
	}
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function set_status(Request $request) {
		[
			'contractId'	=> $contractId,
			'key' 			=> $key
		] = $request->validate([
			'contractId' 	=> 'required|numeric',
			'key' 			=> 'present|nullable'
		]);
		
		$stat = $this->contract->setStatus($contractId, $key);
		return response()->json($stat);
	}
	
	
	
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function colums() {
		$colums = $this->contract->getContractColums();
		return $this->render('colums', compact('colums'));
	}
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function set_colums(Request $request) {
		$stat = $this->contract->setUserColums($request);
		return response()->json($stat);
	}
	
	
	
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function sortdeps(Request $request) {
		$alldeps = $this->department->get($request)->pluck('name', 'id');
		
		if ($sort = auth('site')->user()->contract_deps) {
			$sortDeps = [];
			foreach ($sort as $id) {
				$sortDeps[$id] = $alldeps[$id];
			}
		} else {
			$sortDeps = $alldeps;
		}
		
		return $this->render('sortdeps', compact('sortDeps'));
	}
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function set_sortdeps(Request $request) {
		$stat = $this->contract->setUserDeps($request);
		return response()->json($stat);
	}
	
	
	
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function check_new(Request $request) {
		$stat = $this->contract->checkNew($request);
		return response()->json($stat);
	}
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function pin(Request $request) {
		$stat = $this->contract->pin($request);
		return response()->json($stat);
	}
	
	
	
	
	
	
	
	
	/** Общая информация
	 * @param 
	 * @return 
	 */
	public function get_common_info(Request $request) {
		if (!$contractId = $request->input('contract_id')) return false;
		$fields = $this->getSettings('contracts-common-info');
		
		$data = [];
		$contractInfo = ContractInfo::select('data')
			->where('contract_id', $contractId)
			->first();
		
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
		]]);
		
		$contract = $this->contract->get($request, true);
		
		if (isset($contractInfo['data']) && is_array($contractInfo['data'])) {
			foreach ($contractInfo['data'] as $fieldId => $value) {
				$data[$fieldId] = $value;
			}
		}
		
		return $this->render('common_info', compact('fields', 'data', 'contract'));
	}
	
	
	
	
	
	/** Общая информация задать
	 * @param 
	 * @return 
	 */
	public function set_common_info(Request $request) {
		$contractId = $request->input('contract_id');
		$fieldId = $request->input('field_id');
		$value = $request->input('value');
		
		$contractInfo = ContractInfo::firstOrNew(['contract_id' => $contractId]);
		$data = $contractInfo->data;
		if ($value) {
			$contractInfo->data = data_set($data, $fieldId, $value);
		} else {
			Arr::forget($data, $fieldId);
			$contractInfo->data = $data;
		}
		$stat = $contractInfo->save();
		return $stat;
	}
	
	
	
	
	
	/** Общая информация очистить данные полей, которые удалили
	 * @param 
	 * @return 
	 */
	public function clear_common_info(Request $request) {
		$fieldId = $request->input('field_id');
		
		foreach (ContractInfo::cursor() as $contractInfo) {
			$data = $contractInfo->data;
			Arr::forget($data, $fieldId);
			if (!empty($data)) {
				$contractInfo->data = $data;
				$contractInfo->save();
			} else {
				$contractInfo->delete();
			}
		}
		return true;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function chat_get(Request $request) {
		$contractId = $request->get('contract_id');
		$accountId = auth('site')->user()->id;
		
		$messages = ContractChat::where('contract_id', $contractId)
			->with('user')
			->get()
			->map(function($item) use($accountId) {
				$item['self'] = $item['account_id'] == $accountId;
				$item['name'] = $item['user']['pseudoname'] ?? $item['user']['name'] ?? 'Анонимный сотрудник';
				$item['date'] = Carbon::parse($item['created_at'])->format('Y-m-d');
				return $item;
			});
		
		return $this->render('chat.list', compact('messages', 'contractId'));
	}
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function chat_send(Request $request) {
		$contractId = $request->input('contract_id');
		$message = $request->input('message');
		
		$createdMessage = ContractChat::create([
			'contract_id' 	=> $contractId,
			'account_id' 	=> auth('site')->user()->id,
			'message' 		=> $message,
		]);
		
		$createdMessage['self'] = true;
		$createdMessage['name'] = $createdMessage->user['pseudoname'] ?? $createdMessage->user['name'] ?? 'Анонимный сотрудник';
		
		if ($createdMessage) return $this->render('chat.item', $createdMessage);
		return response()->json(false);
	}
	
	
	
	
	
	
	
	
	
	/** Отправить в чаты договоров из подборки
	 * @param 
	 * @return 
	 */
	public function chat_send_many(Request $request) {
		$selectionId = $request->input('selectionId');
		$contractIds = $request->input('contractIds');
		$message = $request->input('message');
		
		if ($selectionId) {
			$selection = Selection::find($selectionId);
			$contracts = $selection->contracts->pluck('id');
		} elseif ($contractIds) {
			$contracts = collect($contractIds);
		}
		
		if ($contracts->isEmpty()) return response()->json(-1);
		
		$insertData = [];
		$now = now();
		foreach ($contracts as $contractId) {
			$insertData[] = [
				'contract_id' 	=> $contractId,
				'account_id' 	=> auth('site')->user()->id,
				'message' 		=> $message,
				'created_at'	=> $now,
    			'updated_at'	=> $now,
			];
		}
		
		$inserted = ContractChat::insert($insertData);
		return response()->json($inserted);
	}
	
	
	
	
	
	
	
	
	public function step_checkbox(Request $request) {
		[
			'contractId' 	=> $contractId,
			'departmentId' 	=> $departmentId,
			'stepId' 		=> $stepId,
			'value' 		=> $value,
		] = $request->validate([
			'contractId' 	=> 'required|numeric',
			'departmentId' 	=> 'required|numeric',
			'stepId' 		=> 'required|numeric',
			'value' 		=> 'required|boolean',
		]);
		
		$initStepsData = ContractDepartment::firstOrNew([
			'contract_id' 	=> $contractId,
			'department_id' => $departmentId,
		], [
			'show'			=> 1,
			'updated_show'	=> now()->setTime(0, 0, 0),
		]);
		
		$steps = $initStepsData->steps ?? [];
		
		if ($value) {
			//$stepsArrKey = array_search($stepId, array_column($steps, 'step_id'));
            if ($steps) Arr::forget($steps, $stepId);
			ContractData::where(['contract_id' => $contractId, 'department_id' => $departmentId, 'step_id' => $stepId])->delete();
        } else {
			$stepData = Step::find($stepId);
			$steps[] = ['step_id' => $stepId, 'deadline' => $stepData->deadline];
        }
		
		$initStepsData->steps = array_values($steps);
       
	    $stat = $initStepsData->save();
		return response()->json($stat);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	/** Получить все уникальные значения выбранной колонки
	 * @param 
	 * @return 
	 */
	public function column_values(Request $request) {
		$columnName = $request->get('column');
		$currentList = $request->get('currentList');
		$values = $this->contract->getColumnValues($columnName, $currentList);
		return response()->json($values);
	}
	
	
	
	
	
	
	
	
	public function calendar(Request $request) {
		return $this->render('calendar');
	}
	
	
	
	
	
	
	// Настройки пользователя
	public function settings() {
		$settings = $this->user->getSettings(request('setting'));
		return $this->render('settings', compact('settings'));
	}
	
	public function set_setting(Request $request) {
		[
			'setting' 		=> $setting,
			'value' 		=> $value,
		] = $request->validate([
			'setting' 		=> 'required|string',
			'value' 		=> 'required',
		]);
		
		$stat = $this->user->setSetting($setting, $value);
		
		return response()->json($stat);
	}
	
	
	
	
	
	
	
	
	// Комментарии чекбокса
	public function cell_comment(Request $request) {
		$cellCommentData = $request->validate([
			'contract_id'	=> 'required|integer',
			'department_id'	=> 'required|integer',
			'step_id' 		=> 'required|integer',
		]);
		$comment = $this->contract->getCellComment($cellCommentData);
		return $this->render('cell_comments', compact('comment'));
	}
	
	public function set_cell_comment(Request $request) {
		$cellCommentData = $request->validate([
			'contract_id'	=> 'required|integer',
			'department_id'	=> 'required|integer',
			'step_id' 		=> 'required|integer',
			'comment' 		=> 'present|string|nullable',
		]);
		
		$stat = $this->contract->setCellComment($cellCommentData);
		
		return response()->json($stat);
	}
	
	
	






	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/**
	 * Добавить сотрудников отделов для выпадающего списка
	 * @param 
	 * @return 
	 */
	private function _addDepsUsersToData($alldeps = null) {
		$this->data['deps_users'] = [];
		if (!$alldeps) return false;
		$depsUsers = $this->department->getUsersToAssign($alldeps, 'pseudoname');
		$this->data['deps_users'] = $depsUsers ?? [];
	}
	
	
}