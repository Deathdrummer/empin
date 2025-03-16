<?php namespace App\Http\Controllers\site;

use App\Enums\ContractColums;
use App\Enums\VirtualVars;
use App\Exports\ContractsExport;
use App\Helpers\DdrDateTime;
use App\Http\Controllers\Controller;
use App\Imports\TemplatesImport;
use App\Models\Contract;
use App\Models\ContractChat;
use App\Models\ContractData;
use App\Models\ContractDepartment;
use App\Models\ContractFile;
use App\Models\ContractInfo;
use App\Models\Department;
use App\Models\Selection;
use App\Models\Step;
use App\Services\Business\Department as DepartmentService;
use App\Services\Business\Contract as ContractService;
use App\Services\Business\User as UserService;
use App\Services\Business\VirtualVars as BusinessVirtualVars;
use App\Traits\Renderable;
use App\Traits\Settingable;
use Carbon\Carbon;
use Error;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\File;
use Tochka\Calendar\WorkCalendar;
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
			$headers['x-count-contracts-gencontracting'] = $counts['gencontracting'] ?? null;
			
			
			$headers['x-count-current-list'] = match(true) {
				$request->get('archive', false) == 1 => $counts['archive'],
				!!$request->get('department_id', false) && isset($counts['departments'][$request->get('department_id')]) => $counts['departments'][$request->get('department_id')],
				!$request->has('department_id') && $request->get('archive', false) == 0 => $counts['all'],
				default	=> null,
			};
		}
		
		
		$columnFilter = null;
		if ($filterRequest = json_decode($request->get('filter', null), true)) {
			foreach ($filterRequest as $item) {
				if ($item['column'] == 'step') {
					$columnFilter[] = $item['column'].':'.$item['value'][1];
				} else {
					$columnFilter[] = $item['column'];
				}
			}
			
			$columnFilter = array_unique($columnFilter);
		}
		
		
		
		if (!$list || $list->isEmpty()) return response()->json(null)->withHeaders($headers); //return $this->renderWithHeaders('list', compact('columnFilter'), $headers);
		
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
		
		$user = auth('site')->user();
		
		$canEditAll = auth('site')->user()->can('dostup-ko-vsem-otdelam:site');
		$isArchive = $request->has('archive') && $request->get('archive') == 1;
		$isDepartment = $request->has('department_id');
		$departmentId = $request->get('department_id');
		
		$edited = ($canEditAll && (!$isArchive || $user->can('edit-archive:site'))) || $isDepartment;
		$searched = $request->has('search') && $request->get('search');
		
		
		$sortField = $request->get('sort_field', 'id');
		$sortOrder = $request->get('sort_order', 'asc');
		
		$selectionEdited = $request->has('edit_selection') && $request->get('edit_selection');
		
		$append = $request->has('append') && $request->get('append');
		
		$allSelections = $searched ? Selection::toChoose()->get()->mapWithKeys(function($item) {
				return [$item['id'] => $item['title']];
			})->toArray() : null;
		
		
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
		$rules .= ','.($user->can('contract-col-can-edit-acts:site') ? '1' : '0'); // вернуть договор в работу из архива
		$rules .= ','.($request->get('can_edit_selection')  ? '1' : '0'); // Можно ли удалять (и другие операции) договоры подборки
		
		
		$this->addSettingToGlobalData([[
			'setting'	=> 'contracts-smeta:contractsSmeta',
			'key'		=> 'id',
		]]);
		
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
	
	
	
	
	
	/** Отобразить подборки, в которых присутствует договор
	 * @param 
	 * @return 
	 */
	public function contract_selections() {
		$contractId = request('contract_id', null);
		if (is_null($contractId)) return response()->json(false);
		
		$selections = $this->contract->getSelections($contractId);
		return $this->render('contract_selections', compact('selections'));
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
		$hasDepsContractIds = $dept->contracts()->wherePivotNotNull('steps')->where(function($query) {
				$query->where('contract_department.show', 0);
				$query->orWhere(function($q) {
					$q->where('contract_department.show', 1);
					$q->where('contract_department.hide', 1);
				});
			})->get()->pluck('id');
		
		$dataToUpdate = [];
		foreach ($contractIds as $countractId) {
			if (!$hasDepsContractIds->contains($countractId)) continue;
			$dataToUpdate[$countractId] = ['show' => 1, 'hide' => 0, 'updated_show' => now()->setTime(0, 0, 0)];
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
		
		if (!$userSortDeps = auth('site')?->user()?->contract_deps) {
			$sortDeps = $alldeps;
			return $this->render('sortdeps', compact('sortDeps'));
		}
		
		$sortDeps = [];
		foreach ($userSortDeps as $id) {
			$sortDeps[$id] = $alldeps[$id];
		}

		$nonSortedDeps = array_diff_key($alldeps->toArray(), $sortDeps);
		
		$sortDeps = array_replace($sortDeps, $nonSortedDeps);
		
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
	
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function colorselections() {
		$colorsList = $this->getSettings('contract-selection-colors');
		
		usort($colorsList, function($a, $b) {
			return $a['sort'] <=> $b['sort'];
		});
		
		array_push($colorsList, [
			'id' 	=> null,
			'name' 	=> 'Снять выделение',
			'color'	=> null,
		]);
		return response()->json($colorsList);
	}
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function set_colorselection(Request $request) {
		[
			'contractIds' 	=> $contractIds,
			'colorId' 		=> $colorId,
		] = $request->validate([
			'contractIds' 	=> 'required|array',
			'colorId' 		=> 'numeric|nullable',
		]);
		
		$color = $this->user->setContractColor($contractIds, $colorId);
		return response()->json(['color' => !is_null($color) ? $color.'75' : null]);
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
		
		$files = null;
		if ($contractfiles = ContractFile::GetByConmtractId($contractId)->get()) {
			$files = $contractfiles->filter(function($row) {
				$path = 'contracts/'.$row['contract_id'].'/'.$row['filename_sys'];
				if (!Storage::exists($path)) return false;
				
				if (!$row['is_image']) {
					$extension = File::extension($path);
					$row['thumb'] = "/assets/images/filetypes/{$extension}.png";
					return true;
				} 
				
				$image = Image::read('storage/'.$path);
				$thumb = $image->scale(height: 70);
				$row['thumb'] = $thumb->toGif()->toDataUri();
				return true;
			});
		}
		
		return $this->render('common_info', compact('fields', 'data', 'contract', 'files'));
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
			'value' 		=> $hasCheckox,
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
		
		if ($hasCheckox) {
			//$stepsArrKey = array_search($stepId, array_column($steps, 'step_id'));
            if ($steps) Arr::forget($steps, $stepId);
			ContractData::where(['contract_id' => $contractId, 'department_id' => $departmentId, 'step_id' => $stepId])->delete();
        } else {
			$stepData = Step::find($stepId);
			$steps[] = ['step_id' => $stepId, 'deadline' => $stepData->deadline];
        }
		
		$initStepsData->steps = array_values($steps);
       
	    if (!$stat = $initStepsData->save()) return response()->json($stat);
		
		if (!$hasCheckox) {
			$depsUsers = $this->department->getUsersToAssign([$departmentId], ['id:value', 'full_name:title', 'dismissed:disabled', 'dismissed:hidden']);
			$list = $depsUsers[$departmentId] ?? null;
			return response()->json($list);
		}
		
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
	
	public function work_calendar_count(Request $request) {
		[
			'year' 			=> $year,
			'month'			=> $month,
			'day'			=> $day,
			'count_days' 	=> $countDays,
			'add_work_days' => $addWorkdays,
		] = $request->validate([
			'year' 			=> 'required|string',
			'month'			=> 'required|string',
			'day' 			=> 'required|string',
			'count_days' 	=> 'required|numeric',
			'add_work_days' => 'present|boolean',
		]);


		$date = WorkCalendar::create($year, $month, $day);
		if (!$addWorkdays) {
			$date->addDay($countDays);
		} else {
			$date->addWorkdays($countDays);
		}
		return response()->json($date);
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
	
	
	
	
	
	
	// Светофор по смете
	public function cell_lights(Request $request) {
		[
			'color' 		=> $color,
			'contract_id' 	=> $contractId,
			'department_id'	=> $departmentId,
			'step_id' 		=> $stepId,
		] = $request->validate([
			'color'			=> 'nullable|integer',
			'contract_id'	=> 'required|integer',
			'department_id'	=> 'required|integer',
			'step_id'		=> 'required|integer',
		]);
		
		$this->addSettingToGlobalData('contracts-smeta:contractsSmeta');
		
		return $this->render('cell_lights', compact('color', 'contractId', 'departmentId', 'stepId'));
	}
	
	
	
	
	
	
	
	// Редактировать ячейку
	public function cell_edit(Request $request) {
		[
			'contract_id'	=> $contractId,
			'column' 		=> $column,
			'type' 			=> $type,
		] = $request->validate([
			'contract_id'	=> 'required|integer',
			'column'		=> 'required|string',
			'type' 			=> 'required|integer',
		]);
		
		
		
		
		$headers = [];
		
		$data = $this->contract->getCellData($contractId, $column);
		
		$data['list'] = null;
		
		if ($type == 2) {
			$headers['price_nds'] = $this->getSettings('price-nds');
			
		} else if ($type == 4) {
			$data['list'] = match ($column) {
				'customer' 		=> $this->getSettings('contract-customers:customers'),
				'type' 			=> $this->getSettings('contract-types:types'),
				'contractor'	=> $this->getSettings('contract-contractors:contractors'),
				'act_pir'		=> [['id' => 1, 'name' => 'Да'], ['id' => 0, 'name' => 'Нет']],
			};
		}
		
		$data['type'] = $type;
		
		return $this->render('cell_edit', $data, [], $headers);
	}
	
	
	public function set_cell_edit(Request $request) {
		[
			'contract_id'	=> $contractId,
			'column' 		=> $column,
			'type' 			=> $type,
			'data' 			=> $data,
			'addict_colums'	=> $addictColums,
		] = $request->validate([
			'contract_id'	=> 'required|integer',
			'type'			=> 'required|integer',
			'column'		=> 'required|string',
			'data' 			=> 'present|nullable',
			'addict_colums'	=> 'present|array|nullable',
		]);
		
		$stat = $this->contract->setCellData($contractId, $column, $type, $data, $addictColums);
		
		if ($type == 4) {
			$listData = match ($column) {
				'type' 			=> array_column($this->getSettings('contract-types'), 'title', 'id'),
				'contractor' 	=> array_column($this->getSettings('contract-contractors'), 'name', 'id'),
				'customer' 		=> array_column($this->getSettings('contract-customers'), 'name', 'id'),
				'act_pir'		=> [1 => '<i class="fa-solid fa-circle-check color-green fz16px"></i>', 0 => ''],
			};
			
			return $listData[$data] ?? null;
		}
		
		return response()->json($stat);
	}
	
	








	/** Экспорт данных в Excel
	 * @param 
	 * @return 
	 */
	public function get_to_export(Request $request) {
		$colums = $this->contract->getContractColums();
		
		unset($colums['period']);
		
		$height = $request['height'] ?? null;
		
		return $this->render('colums_to_export', compact('colums', 'height'));
	}
	
	/**
	 * @param 
	 * @return 
	 */
	public function set_to_export(Request $request) {
		$params = $request->validate([
			'contracts_ids'	=> 'exclude_unless:selection_id,null',
			'selection_id'	=> 'exclude_unless:contracts_ids,null',
			'colums'		=> 'required|array',
			'sort'			=> 'filled|string',
			'order'			=> 'filled|string',
		]);
		
		return Excel::download(new ContractsExport($params), 'contracts.xlsx');
	}
	
	
	
	
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function get_edit_acts_form() {
		return $this->render('edit_acts/form');
	}
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function set_edit_acts(Request $request) {
		[
			'contracts_ids'		=> $contractsIds,
			'act_pir' 			=> $actPir,
			'date_send_action'	=> $dateSendAction,
			'count_ks_2'		=> $countKs2,
		] = $request->validate([
			'contracts_ids'		=> 'required|array',
			'act_pir' 			=> 'nullable|boolean',
			'date_send_action'	=> 'nullable|date',
			'count_ks_2'		=> 'nullable|integer',
		]);
		
		$stat = Contract::whereIn('id', $contractsIds)->update([
			'act_pir' 			=> $actPir,
			'date_send_action'	=> DdrDateTime::buildTimestamp($dateSendAction),
			'count_ks_2'		=> $countKs2,
		]);
		
		return response()->json($stat > 0);
	}
	
	
	
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function export_act_form() {
		
		$allTemplates = $this->getSettingsCollect('templates-to-export');
		
		$templates = $allTemplates->filter(function (array $value, int $key) {
			return !isset($value['ranged']) && $value['show'] == 1 && (!isset($value['rule']) || auth('site')->user()->can($value['rule']));
		});
		
		$rangeTemplates = $allTemplates->filter(function (array $value, int $key) {
			return isset($value['ranged']) && $value['show'] == 1 && (!isset($value['rule']) || auth('site')->user()->can($value['rule']));
		});
		
		return $this->render('export_acts/form', compact('templates', 'rangeTemplates'));
	}
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function export_act(Request $request) {
		[
			'contract_id'	=> $contractId,
			'template_id'	=> $templateId,
		] = $request->validate([
			'contract_id'	=> 'required|array',
			'template_id'	=> 'required|numeric',
		]);
		
		$ranged = $request->input('ranged');
		
		$contractsData = Contract::find($contractId);
		
		$templateData = $this->getSettingsCollect('templates-to-export')->firstWhere('id', $templateId);
		
		if (!isset($templateData['file']['path']) || !isset($templateData['file']['ext'])) return response()->json(false);
		
		
		[$exportFileName, $exportFilePath] = match($templateData['file']['ext']) {
			'docx'	=> $this->_buildByDocxTemplate($contractsData, $templateData, $ranged),
			'xlsx'	=> $this->_buildByXlsxTemplate($contractsData, $templateData, $ranged),
			default	=> [null, null],
		};
		
		if (!$exportFileName || !$exportFilePath) {
			throw new Error('Ошибка export_act!');
			return false;
		}
		
		return response()->download($exportFilePath, null, ['x-export-filename' => urlencode($exportFileName)])->deleteFileAfterSend();
	}
	
	
	
	
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function export_act_template(Request $request) {
		[
			'path'	=> $path,
			'name'	=> $name,
		] = $request->validate([
			'path'	=> 'required|string',
			'name'	=> 'required|string',
		]);
		
		return response()->download("storage/{$path}", null, ['x-export-filename' => urlencode($name)]);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	#------------------------------------------------------ Формирование шаблонов для экспорта
	
	/**
	* 
	* @param 
	* @return 
	*/
	private function _buildByDocxTemplate($contractsData = null, $templateData = null, $ranged = false) {
		if (!$contractsData || !$templateData) return false;
		
		$templateProcessor = new TemplateProcessor('storage/'.$templateData['file']['path']);
		
		$buildContractsdata = $this->_buildContractsdata($contractsData);
		
		$tempVars = $templateProcessor->getVariables();
		
		
		$setValuesData = [];
		foreach ($tempVars as $variable) {
			$removeSeparatorVariable = preg_replace('/#.+#/', '', $variable);
			
			foreach($buildContractsdata as $k => $buildContractdata) {
				 $buildedVariable = preg_replace_callback('/\[([a-z_]+)\]/', function($matches) use($buildContractdata) {
					return match(true) {
						DdrDateTime::isValidDateTime($buildContractdata[$matches[1]] ?? '') => DdrDateTime::convertDateFormat($buildContractdata[$matches[1]]),
						default	=> $buildContractdata[$matches[1]] ?? null,
					};
				}, $removeSeparatorVariable);
				
				
				if (strpos($buildedVariable, '::') !== false) {
					preg_match('/([a-z_]+)::(.+)/', $buildedVariable, $matches);
					[, $parsedVar, $formatStr] = $matches;
					
					if (!isset($buildContractdata[$parsedVar])) {
						$setValuesData[$variable][$k] = '';
						continue;
					} 
					
					$parsedData = match(true) {
						DdrDateTime::isValidDateTime($buildContractdata[$parsedVar] ?? '') => DdrDateTime::convertDateFormat($buildContractdata[$parsedVar]),
						default	=> $buildContractdata[$parsedVar] ?? '',
					};
					
					$setValuesData[$variable][$k] = sprintf($formatStr, $parsedData);
					continue;
				}
				
				if (strpos($buildedVariable, '||') !== false) {
					preg_match('/([a-z_]+)\|\|(.+)/', $buildedVariable, $matches);
					[, $parsedVar, $formatStr] = $matches;
					
					$resVal = isset($buildContractdata[$parsedVar]) && $buildContractdata[$parsedVar] ? $formatStr : null;
					
					$setValuesData[$variable][$k] = $resVal;
					continue;
				}
				
				if (!isset($buildContractdata[$variable])) {
					$setValuesData[$variable][$k] = '';
					continue;
				}
				
				$parsedData = match(true) {
					DdrDateTime::isValidDateTime($buildContractdata[$variable] ?? '') => DdrDateTime::convertDateFormat($buildContractdata[$variable]),
					default	=> $buildContractdata[$variable] ?? '',
				};
				
				
				$setValuesData[$variable][$k] = $parsedData;

			}
		}
		
		
		foreach ($setValuesData as $variable => $dataItems) {
			preg_match('/#(.+)#/', $variable, $matches);
			$templateProcessor->setValue($variable, implode("\n", $dataItems));
		}
		
		
		# для заголовков
		$colums = ContractColums::getKeys();
		$virtVars = VirtualVars::getKeys();
		$varsTitlesMap = [];
		
		foreach ($colums as $column) {
			$varsTitlesMap['{'.$column.'}'] = match(true) {
				DdrDateTime::isValidDateTime($buildContractsdata[0][$column] ?? '')	=> DdrDateTime::convertDateFormat($buildContractsdata[0][$column]),
				default	=> $buildContractsdata[0][$column] ?? '',
			};
		}
		
		foreach ($virtVars as $virtVar) {
			$varsTitlesMap['{'.$virtVar.'}'] = BusinessVirtualVars::run($virtVar, $buildContractsdata[0]);
		}
		
		$buildedExportFileName = str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], '_', trim(Str::swap($varsTitlesMap, $templateData['export_name'] ?? $contractsData[0]['id'])));
		$exportFilePath = "storage/{$buildedExportFileName}.{$templateData['file']['ext']}";
		$exportFileName = "{$buildedExportFileName}.{$templateData['file']['ext']}";
		
		$templateProcessor->saveAs($exportFilePath);
		
		return [$exportFileName, $exportFilePath];
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	private function _buildByXlsxTemplate($contractsData = null, $templateData = null, $ranged = false) {
		if (!$contractsData || !$templateData) return false;
		
		$spreadsheet = IOFactory::load('storage/'.$templateData['file']['path']);
		
		$sheetCount = $spreadsheet->getSheetCount();
		
		$variables = $this->_getXlsxVariables($spreadsheet, $sheetCount);
		
		$buildContractsdata = $this->_buildContractsdata($contractsData, $ranged);
		
		for ($i = 0; $i < $sheetCount; $i++) {
			$sheet = $spreadsheet->getSheet($i);
			$highestRow = $sheet->getHighestRow();
			$highestColumn = $sheet->getHighestColumn();
			$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
			
			
			$varsMap = [];
			
			foreach ($variables as $variable) {
				$removeSeparatorVariable = preg_replace('/#.+#/', '', $variable);
				
				foreach($buildContractsdata as $k => $buildContractdata) {
					$buildedVariable = preg_replace_callback('/\[([a-z_]+)\]/', function($matches) use($buildContractdata) {
						return match(true) {
							DdrDateTime::isValidDateTime($buildContractdata[$matches[1]] ?? '') => DdrDateTime::convertDateFormat($buildContractdata[$matches[1]]),
							default	=> $buildContractdata[$matches[1]] ?? null,
						};
					}, $removeSeparatorVariable);
					
					
					if (strpos($buildedVariable, '::') !== false) {
						preg_match('/([a-z_]+)::(.+)/', $buildedVariable, $matches);
						[, $parsedVar, $formatStr] = $matches;
						
						if (!isset($buildContractdata[$parsedVar])) continue;
						
						$parsedData = match(true) {
							DdrDateTime::isValidDateTime($buildContractdata[$parsedVar] ?? '')	=> DdrDateTime::convertDateFormat($buildContractdata[$parsedVar]),
							default	=> $buildContractdata[$parsedVar] ?? '',
						};
						
						$varsMap['${'.$variable.'}'][$k] = sprintf($formatStr, $parsedData);
						continue;
					}
					
					if (strpos($buildedVariable, '||') !== false) {
						preg_match('/([a-z_]+)\|\|(.+)/', $buildedVariable, $matches);
						[, $parsedVar, $formatStr] = $matches;
						
						$resVal = isset($buildContractdata[$parsedVar]) && $buildContractdata[$parsedVar] ? $formatStr : '';
						
						$varsMap['${'.$variable.'}'][$k] = $resVal;
						
						continue;
					}
					
					if (!isset($buildContractdata[$variable])) continue;
					
					$varsMap['${'.$variable.'}'][$k] = $buildContractdata[$variable] ?? '';
				}
			}
			
			
			
			if ($ranged) {
				$varsMap = $this->_convertVarsToRange($varsMap);
			} else {
				foreach ($varsMap as $variable => $dataItems) {
					$varsMap[$variable] = $dataItems[0];
				}
			}
			
			
			
			if ($ranged) {
				$usedCells = [];
				for ($row = 1; $row <= $highestRow; $row++) {
					for ($col = 1; $col <= $highestColumnIndex; $col++) {
						$cellAddress = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
						$cell = $sheet->getCell($cellAddress);
						$cellValue = $cell->getValue();
						if (!$cellValue || !preg_match('/\$\{.+\}/', $cellValue) || in_array($cellAddress, $usedCells)) continue;
						
						foreach ($varsMap as $k => $varsItems) {
							$newVal = trim(Str::swap($varsItems, $cellValue));
							$cellAddress = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row+$k;
							$usedCells[] = $cellAddress;
							$sheet->setCellValue($cellAddress, $newVal);
						}
					}
				}
			} else {
				for ($row = 1; $row <= $highestRow; $row++) {
					for ($col = 1; $col <= $highestColumnIndex; $col++) {
						$cell = $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row);
						$cellValue = $cell->getValue();
						if (!$cellValue || !preg_match('/\$\{.+\}/', $cellValue)) continue;
						$newVal = trim(Str::swap($varsMap, $cellValue));
						if (!$newVal) continue;
						$cellAddress = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
						$sheet->setCellValue($cellAddress, $newVal);
					}
				}
			}
			
			
			
			
			# для заголовков
			$colums = ContractColums::getKeys();
			$virtVars = VirtualVars::getKeys();
			$varsTitlesMap = [];
			
			foreach ($colums as $column) {
				$varsTitlesMap['{'.$column.'}'] = $buildContractsdata[0][$column] ?? '';
			}
			
			foreach ($virtVars as $virtVar) {
				$varsTitlesMap['{'.$virtVar.'}'] = BusinessVirtualVars::run($virtVar, $buildContractsdata[0]) ?? '';
			}
		}
		
		$buildedExportFileName = str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], '_', trim(Str::swap($varsTitlesMap, $templateData['export_name'] ?? $contractsData[0]['id'])));
		$exportFilePath = "storage/{$buildedExportFileName}.{$templateData['file']['ext']}";
		$exportFileName = "{$buildedExportFileName}.{$templateData['file']['ext']}";
		
		$writer = new Xlsx($spreadsheet);
		$writer->save($exportFilePath);
		
		return [$exportFileName, $exportFilePath];
	}
	
	
	
	
	
	
	












	/**
	* Получить переменные из Excel документа 
	* @param 
	* @return 
	*/
	private function _getXlsxVariables($spreadsheet, $sheetCount) {
		$variables = [];

		for ($i = 0; $i < $sheetCount; $i++) {
			$sheet = $spreadsheet->getSheet($i);
			$sheetContent = $sheet->toArray();

			foreach ($sheetContent as $row) {
				foreach ($row as $cell) {
					if (preg_match_all('/\$\{([^\}]+)\}/', $cell, $matches)) {
						foreach ($matches[1] as $variable) {
							$variables[] = $variable;
						}
					}
				}
			}
		}
		
		$variables = array_unique($variables);
		return $variables;
	}

	
	
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	private function _convertVarsToRange($varsMap = null) {
		if (!$varsMap) return null;
		
		$resArr = [];
		
		foreach ($varsMap as $variable => $varsItems) {
			foreach ($varsItems as $k => $var) {
				$resArr[$k][$variable] = $var;
			} 
		}
		
		return $resArr;
	}
	
	
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	private function _mergeContractsFields(array $inputArray, string $separator) {
		$result = [];

		foreach ($inputArray as $item) {
			foreach ($item as $key => $value) {
				$parsedValue = match(true) {
					DdrDateTime::isValidDateTime($value ?? '')	=> DdrDateTime::convertDateFormat($value),
					default	=> $value ?? '',
				};
				
				if (array_key_exists($key, $result)) {
					$result[$key] .= $separator.$parsedValue;
				} else {
					$result[$key] = $parsedValue;
				}
			}
		}

		return collect($result);
	}
	
	
	
	
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	private function _buildContractsdata($contractsData = null, $ranged = false) {
		if ($contractsData?->isEmpty()) return false;

		$virtVars = VirtualVars::getKeys();
		
		['contractor' => $contractor, 'customer' => $customer, 'type' => $type] = $this->getSettings([[
				'setting'	=> 'contract-customers:customer',
				'key'		=> 'id',
				'value'		=> 'name'
			], [
				'setting'	=> 'contract-types:type',
				'key'		=> 'id',
				'value'		=> 'title'
			], [
				'setting'	=> 'contract-contractors:contractor',
				'key'		=> 'id',
				'value'		=> 'name'
			]
		]);
		
		$buildedContractsData = [];
		foreach ($contractsData as $k => $contractItem) {
			foreach ($contractItem?->toArray() as $column => $value) {
				$buildedContractsData[$k][$column] = match($column) {
					'contractor'	=> $contractor[$value] ?? '',
					'customer'		=> $customer[$value] ?? '',
					'type'			=> $type[$value] ?? '',
					'price' 		=> str_replace('.', ',', $value),
					'price_nds' 	=> str_replace('.', ',', $value),
					'price_sub' 	=> str_replace('.', ',', $value),
					'price_sub_nds' => str_replace('.', ',', $value),
					'price_gen' 	=> str_replace('.', ',', $value),
					'price_gen_nds' => str_replace('.', ',', $value),
					'price_avvr' 	=> str_replace('.', ',', $value),
					'price_avvr_nds'=> str_replace('.', ',', $value),
					'avvr_nds_only' => str_replace('.', ',', $value),
					'price_pir' 	=> str_replace('.', ',', $value),
					'price_pir_nds' => str_replace('.', ',', $value),
					'pir_nds_only' 	=> str_replace('.', ',', $value),
					'price_smr' 	=> str_replace('.', ',', $value),
					'price_pnr' 	=> str_replace('.', ',', $value),
					default			=> $value ?? '',
				};
			}
		}
		
		
		
		
		foreach ($virtVars as $virtVar) {
			foreach ($buildedContractsData as $k => $buildedContractData) {
				$buildedContractsData[$k][$virtVar] = BusinessVirtualVars::run($virtVar, $buildedContractData);
			}
			
		}
		
		return $buildedContractsData;
	}
	
	
	
	
	
	
	
	
	/**
	 * Добавить сотрудников отделов для выпадающего списка
	 * @param 
	 * @return 
	 */
	private function _addDepsUsersToData($alldeps = null) {
		$this->data['deps_users'] = [];
		if (!$alldeps) return false;
		$depsUsers = $this->department->getUsersToAssign($alldeps, ['id:value', 'full_name:title', 'dismissed:disabled', 'dismissed:hidden']);
		$this->data['deps_users'] = $depsUsers ?? [];
	}
	
	
}