<?php namespace App\Services\Business;

use App\Http\Filters\ContractFilter;
use App\Models\Contract as ContractModel;
use App\Models\ContractData;
use App\Models\ContractDepartment;
use App\Models\ContractCellComment;
use App\Models\Selection;
use App\Models\User;
use App\Services\Business\Department as DepartmentService;
use App\Services\Business\User as UserService;
use App\Services\DateTime;
use App\Traits\Settingable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class Contract {
	use Settingable; 
	
	private $datetime;
	private $department;
	private $user;
	
	private $allColumsMap = [
		'object_number' 	=> 'Номер объекта',
		'title' 			=> 'Название',
		'titul' 			=> 'Титул',
		'customer' 			=> 'Заказчик',
		'contractor' 		=> 'Исполнтель',
		'type' 				=> 'Тип договора',
		'contract' 			=> 'Номер договора',
		'applicant' 		=> 'Заявитель',
		'locality' 			=> 'Населенный пункт',
		'date_start' 		=> 'Дата подписания договора',
		'date_end' 			=> 'Дата окончания работ по договору',
		'price_nds' 		=> 'Стоимость договора с НДС',
		'price' 			=> 'Стоимость договора без НДС',
		'buy_number' 		=> 'Номер закупки',
		'date_buy' 	 		=> 'Дата закупки',
		'hoz_method' 		=> 'Хоз способ',
		'subcontracting' 	=> 'Субподряд',
		'gencontracting' 	=> 'Генподряд',
		'date_close' 	 	=> 'Дата закрытия договора',
		'archive_dir' 		=> 'Архивная папка',
		'period' 			=> 'Срок исполнения договора',
		'archive' 			=> 'В архиве',
	];
	
	
	
	public function __construct(DateTime $datetime, DepartmentService $department, UserService $user) {
		$this->datetime = $datetime;
		$this->department = $department;
		$this->user = $user;
	}
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function get(Request $request, $one = false) {
		
		$queryParams = $request->only([
			'id',
			'contract_id',
			'department_id',
			'archive',
			'show'
		]);
		
		$contractId = $request->input('id') ?: $request->input('contract_id');
		
		
		$filter = app()->make(ContractFilter::class, compact('queryParams'));
		$data = ContractModel::filter($filter)->get();
		$result = $data->mapWithKeysMany(function($item) {
			return [$item['id'] => [
				'id'				=> $item['id'] ?? null,
				'object_number'		=> $item['object_number'] ?? null,
				'buy_number'		=> $item['buy_number'] ?? null,
				'without_buy'		=> $item['without_buy'] ?? null,
				'title'				=> $item['title'] ?? null,
				'applicant'			=> $item['applicant'] ?? null,
				'titul' 			=> $item['titul'] ?? null,
				'contract' 			=> $item['contract'] ?? null,
				'subcontracting' 	=> $item['subcontracting'] ?? null,
				'gencontracting' 	=> $item['gencontracting'] ?? null,
				'customer' 			=> $item['customer'] ?? null,
				'locality' 			=> $item['locality'] ?? null,
				'price' 			=> $item['price'] ?? null,
				'price_nds' 		=> $item['price_nds'] ?? null,
				'gen_percent' 		=> $item['gen_percent'] ?? null,
				'date_start' 		=> $item['date_start'] ?? null,
				'date_end' 			=> $item['date_end'] ?? null,
				'date_close' 		=> $item['date_close'] ?? null,
				'date_buy' 			=> $item['date_buy'] ?? null,
				'hoz_method' 		=> $item['hoz_method'] ?? null,
				'type' 				=> $item['type'] ?? null,
				'contractor' 		=> $item['contractor'] ?? null,
				'archive' 			=> $item['archive'] ?? null,
				'archive_dir' 		=> $item['archive_dir'] ?? null,
				'created_at' 		=> $item['created_at'] ?? null,
				'updated_at' 		=> $item['updated_at'] ?? null
			]];
		});
		
		if ($one) return $result[$contractId];
		return $result;
	}
	
	
	
	
	
	
	
	
	
	/**
	 * @param Request $request
	 * @return mixed
	 */
	public function getWithDepartments(Request $request) {
		$filter = app()->make(ContractFilter::class, ['queryParams' => $request->except(['sort_field', 'sort_order'])]);
		
		$sortField = $request->get('sort_field', 'id');
		$sortOrder = $request->get('sort_order', 'asc');
		$limit = $request->get('limit', 25);
		$offset = $request->get('offset', 0);
		$selection = $request->get('selection', null);
		$sortStep = strpos($sortField, ':') !== false ? (substr($sortField, strpos($sortField, ':') - strlen($sortField) + 1)) : null;
		$selectedContracts = request('selected_contracts', []);
		
		if (!$userId = auth('site')->user()->id) return false;
		
		
		$onlyAssignedContractsIds = match (true) {
			$this->department->checkShowOnlyAssigned() => ContractData::select('contract_id')->where([
				'data' 	=> $userId,
				'type'	=> 3
			])->get()->pluck('contract_id'),
			default => false
		};
		
		$selectionContracts = match (true) {
			!is_null($selection) => Selection::where('id', $selection)->with('contracts:id')->first(),
			default => false
		};
		
		$userContractsSettings = $this->user->getSettings('contracts');
		
		
		$data = ContractModel::filter($filter)
			->withCount(['departments as has_deps_to_send' => function (Builder $query) {
				$query->where(['show' => 0, 'hide' => 0]);
			}, 'departments as hide_count' => function (Builder $query) {
				$query->where('hide', 1);
			}])
			->withCount('messages')
			->with('departments')
			->with(['selections' => function ($query) use($userId) {
				$query->where('account_id', $userId)
					->orWhereJsonContains('subscribed', $userId)
					->orderBy('_sort', 'ASC');
			}])
			/* ->with('selections') */
			->when($onlyAssignedContractsIds, function ($query) use($onlyAssignedContractsIds) {
				return $query->whereIn('id', $onlyAssignedContractsIds);
			})
			->when($selectionContracts, function ($query) use($selectionContracts) {
				return $query->whereIn('id', $selectionContracts->contracts->pluck('id'));
			})
			->when($sortStep, function ($query) use($sortStep, $sortOrder) {
				$query->orderBy(
					ContractData::select('data')
					->whereColumn('contract_data.contract_id', 'contracts.id')
					->where('contract_data.step_id', $sortStep),
					$sortOrder 
				);
				
				$query->orderBy(
					ContractDepartment::select('show')
					->whereColumn('contract_department.contract_id', 'contracts.id')
					->whereJsonContains('steps', ['step_id' => (int)$sortStep]),
					$sortOrder 
				);
				
			}, function($query) use($sortField, $sortOrder) {
				
				// что тут происходит: производится сортировка по подставным данным. Перечисляются ID сортируемого поля в том порядке, в котором нужно нам.
				
				$settingData = match ($sortField) {
					'type' 			=> array_column($this->getSettings('contract-types'), 'id', 'title'),
					'contractor' 	=> array_column($this->getSettings('contract-contractors'), 'id', 'name'),
					'customer' 		=> array_column($this->getSettings('contract-customers'), 'id', 'name'),
					default => false,
				};
				
				if ($settingData) {
					if ($sortOrder == 'asc') ksort($settingData, SORT_NATURAL);
					elseif ($sortOrder == 'desc') krsort($settingData, SORT_NATURAL);
					
					$ids = array_values($settingData);
					
					// первый вариант
					//$placeholders = implode(',', array_fill(0, count($ids), '?'));
					//$query->orderByRaw("field({$sortField},{$placeholders})", $ids);
					
					// второй вариант
					$implodeIds = implode(',', $ids);
					$query->orderByRaw("FIND_IN_SET($sortField, '$implodeIds')");
				
				} else {
					$query->orderBy($sortField, $sortOrder);
				}
				
			})
			->where(function ($query) use($userContractsSettings) {
				if (isset($userContractsSettings['gencontracting']) && $userContractsSettings['gencontracting']) {
					$query->whereNot('gencontracting', 1);
				}
			})
			->orderBy('id', 'asc')
			->groupBy('id')
			->limit($limit)
			->offset($offset)
			->get();
		
		if ($data->isEmpty()) return false;
		
		
		// Список подборок для каждого договора, в которых он уже добавлен
		//$contractsSelections = [];
		//foreach ($data as $item) $contractsSelections[$item['id']] = $item->selections->pluck('id')->toArray();
		
		
		['pinned' => $pinned, 'viewed' => $viewed] = $this->user->getContractsData();
		
		$deadlinesContracts = $this->getSettings('contracts-deadlines');
		$deadlinesSteps = $this->getSettings('steps-deadlines');
		
		$buildedData = $data->mapWithKeysMany(function($item) use($deadlinesContracts, $deadlinesSteps, $viewed, $pinned, $selectedContracts) {
			if (!is_null($item['deadline_color_key'] )) {
				$forcedColor = $deadlinesContracts[$item['deadline_color_key']]['color']?? null;
				$forcedName = $deadlinesContracts[$item['deadline_color_key']]['name'] ?? '';
			}
			
			if ($deadlinesContracts) {
				$deadlineContractsCondition = $this->datetime->checkDiapason($item['date_end'], $deadlinesContracts, [
					'minSign' 		=> 'min_sign',
					'minDateCount'	=> 'min_count',
					'minDateType' 	=> 'min_datetype',
					'maxSign' 		=> 'max_sign',
					'maxDateCount'	=> 'max_count',
					'maxDateType' 	=> 'max_datetype'
				], ['name', 'color']);
				$color = $deadlineContractsCondition['color'] ?? null;
				$name = $deadlineContractsCondition['name'] ?? '';
			}
			
			
			$departments = $item->departments->mapWithKeys(function($dep) use($item, $deadlinesSteps) {
				$steps = null;
				
				if ($dep['pivot']['steps'] && $deadlinesSteps) {
					foreach ($dep['pivot']['steps'] as $stepId => $step) {
						$steps[$stepId] = $step;
						$dateNow = now()->setTime(0, 0, 0);
						
						$dateStart = Carbon::create($dep['pivot']['updated_show'] ?? $item['date_start']);
						$deadLine = $dateStart->addDays($step['deadline']);
						
						$steps[$stepId]['color'] = match(true) {
							$dateNow < $deadLine => $deadlinesSteps['before'],
							$dateNow == $deadLine => $deadlinesSteps['current'],
							$dateNow > $deadLine => $deadlinesSteps['after'],
							default => 'transparent'
						};
					}
				}
				
				return [$dep['id'] => [
					'id' 				=> $dep['id'] ?? null,
					'name' 				=> $dep['name'] ?? null,
					'assigned_primary'	=> $dep['assigned_primary'] ?? null,
					'show'				=> $dep['pivot']['show'] ?? null,
					'hide'				=> $dep['pivot']['hide'] ?? null,
					'steps'				=> $steps,
				]];
			});
			
			
			return [$item['id'] => [
				'id'				=> $item['id'] ?? null,
				'object_number'		=> $item['object_number'] ?? null,
				'buy_number'		=> $item['buy_number'] ?? null,
				'without_buy'		=> $item['without_buy'] ?? null,
				'title'				=> $item['title'] ?? null,
				'applicant'			=> $item['applicant'] ?? null,
				'titul' 			=> $item['titul'] ?? null,
				'contract' 			=> $item['contract'] ?? null,
				'subcontracting' 	=> $item['subcontracting'] ?? null,
				'gencontracting' 	=> $item['gencontracting'] ?? null,
				'customer' 			=> $item['customer'] ?? null,
				'locality' 			=> $item['locality'] ?? null,
				'price' 			=> $item['price'] ?? null,
				'price_nds' 		=> $item['price_nds'] ?? null,
				'gen_percent' 		=> $item['gen_percent'] ?? null,
				'date_start' 		=> $item['date_start'] ?? null,
				'date_end' 			=> $item['date_end'] ?? null,
				'date_close' 		=> $item['date_close'] ?? null,
				'date_buy' 			=> $item['date_buy'] ?? null,
				'hoz_method' 		=> $item['hoz_method'] ?? null,
				'type' 				=> $item['type'] ?? null,
				'contractor' 		=> $item['contractor'] ?? null,
				'archive' 			=> $item['archive'] ?? null,
				'archive_dir' 		=> $item['archive_dir'] ?? null,
				'created_at' 		=> $item['created_at'] ?? null,
				'updated_at' 		=> $item['updated_at'] ?? null,
				'color' 			=> $color ?? null,
				'name' 				=> $name ?? '',
				'color_forced' 		=> $forcedColor ?? null,
				'name_forced' 		=> $forcedName ?? '',
				'is_new' 			=> isset($viewed[$item['id']]) ? $viewed[$item['id']] == 0 : true,
				'pinned'			=> in_array($item['id'], $pinned) ? -$item['id'] : null,
				
				'has_deps_to_send'	=> $item['has_deps_to_send'] ?? null,
				'ready_to_archive'	=> $item['hide_count'] != 0 && $item['hide_count'] == $item->departments->count(),
				'messages_count'	=> $item['messages_count'] ?? 0,
				'selected'			=> in_array($item['id'], $selectedContracts),
				'selections'		=> $item->selections->pluck('id')->toArray() ?? [],
				'departments' 		=> $departments
			]];
		});
		
		$pinnedItems = $buildedData->filter(function ($item) {
			return $item['pinned'] < 0;
		});
		
		if ($pinnedItems->isEmpty()) return $buildedData;
		
		// Если нужно зафиксировать сортировку закрепленных договоров
		/* $pinnedItems = $pinnedItems->sortBy(function ($item) {
			return $item['pinned'] < 0 ? -$item['pinned'] : false;
		}); */
		
		
		return $pinnedItems->union($buildedData);
	}
	
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function getCounts($request) {
		$filter = app()->make(ContractFilter::class, ['queryParams' => $request->only(['search', 'selection', 'filter'/* , 'archive' */])]);
		
		if (!$userId = auth('site')->user()->id) return false;
		
		$onlyAssignedContractsIds = match (true) {
			$this->department->checkShowOnlyAssigned() => ContractData::select('contract_id')->where([
				'data' 	=> $userId,
				'type'	=> 3
			])->get()->pluck('contract_id'),
			default => false
		};
		
		
		$selectionContracts = false;
		if ($request->has('selection')) {
			$selection = $request->input('selection');
			$selectionContracts = match (true) {
				!is_null($selection) => Selection::where('id', $selection)->with('contracts:id')->first(),
				default => false
			};
		}
		
		
		$countData = ['all' => 0, 'departments' => [], 'archive' => 0];
		
		$data = ContractModel::filter($filter)
			->with('departments:id')
			/* ->when($onlyAssignedContractsIds, function ($query) use($onlyAssignedContractsIds) {
				return $query->whereIn('id', $onlyAssignedContractsIds);
			}) */
			->when($selectionContracts, function ($query) use($selectionContracts) {
				return $query->whereIn('id', $selectionContracts->contracts->pluck('id'));
			})
			->get()
			->toArray();
		
		
		foreach ($data as $item) {
			if ($item['archive'] == 1) {
				$countData['archive'] += 1;
			} elseif (count($item['departments'])) {
				foreach($item['departments'] as $dep) {
					if (!isset($countData['departments'][$dep['id']])) $countData['departments'][$dep['id']] = 0;
					if ($dep['pivot']['show'] == 1 && $dep['pivot']['hide'] == 0) $countData['departments'][$dep['id']] += 1;
				}
				
			}/*  else { // это чтобы показывать количество БЕЗ учета тех, что ейчас в отделах
				$countData['all'] += 1;
			} */
			
			if ($item['archive'] == 0) {
				$countData['all'] += 1;
			}
		}
			
		
		return $countData;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function buildData($contractsIds = null) {
		if (!$contractsIds) $cdata = ContractData::all();
		else $cdata = ContractData::whereIn('contract_id', $contractsIds)->get();
		
		if ($cdata->isEmpty()) return [];
		$contractdata = [];
		foreach ($cdata->toArray() as $item) {
			$contractdata[$item['contract_id']][$item['department_id']][$item['step_id']] = [
				'type' => $item['type'],
				'data' => $item['type'] == 1 ? (int)$item['data'] : $item['data'] 
			];
		}
		return $contractdata;
	}
	
	
	
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function setStatus($contractId = false, $key = null) {
		if (!$contractId) return false;
		$contract = ContractModel::find($contractId);
		$contract->deadline_color_key = $key;
		return $contract->save();
	}
	
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function checkNew(Request $request) {
		return $this->user->checkContractAsViewed($request);
	}
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function pin(Request $request) {
		return $this->user->pinContract($request);
	}
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function getContractColums() {
		$contractColums = auth('site')->user()->contract_colums ?: [];
		
		$allColumsKeys = array_keys($this->allColumsMap);
		
		
		foreach ($contractColums as $k => $field) {
			$indx = array_search($field, $allColumsKeys);
			array_splice($allColumsKeys, $indx, 1);
			array_splice($allColumsKeys, $k-1, 0, $field);
		}
		
		$sortedColums = [];
		foreach ($allColumsKeys as $field) {
			$sortedColums[$field] = [
				'title'		=> $this->allColumsMap[$field],
				'checked'	=> in_array($field, $contractColums),
			];
		}
		
		return $sortedColums;
	}
	
	
	
	
	
	
	
	
	/** Получить значения заданного столбца
	 * @param 
	 * @return 
	 */
	public function getColumnValues($column = null, $currentList = 0) {
		$isAchive = $currentList == -1;
		$department = $currentList > 0 ? $currentList : false;
		
		$columnValues = ContractModel::select($column)
			->when($isAchive, function($query) {
				$query->where('archive', 1);
			}, function($query) {
				$query->whereNot('archive', 1);
			})
			->when($department, function($query) use($department) {
				$query->whereRelation('departments', function(Builder $queryRelation) use($department) {
					$queryRelation->where('department_id', $department);
				});
			})
			->whereNotNull($column)
			->distinct()
			->get()
			->pluck($column)
			->toArray();
		
			
		if (!$columnValues) return false;
		if (!in_array($column, ['customer', 'type', 'contractor'])) return array_combine($columnValues, $columnValues);
		
		
		
		
		
		$settingsData = match ($column) {
			'customer' 		=> $this->getSettings('contract-customers:customers', 'id'),
			'type' 			=> $this->getSettings('contract-types:types'/* , 'id', 'title' */),
			'contractor'	=> $this->getSettings('contract-contractors:contractors', 'id'/* , 'name' */),
		};
		
		if (!$settingsData) return false;
		
		
		$intersectedValues = array_intersect_key($settingsData, array_flip($columnValues));
		
		
		if (in_array($column, ['customer', 'contractor'])) {
			usort($intersectedValues, fn($a, $b) => ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0));
		}
		
		return $intersectedValues;
	}
	
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function getUserColums() {
		return auth('site')->user()->contract_colums ?: array_keys($this->allColumsMap);
	}
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function setUserColums($request) {
		$colums = $request->get('sortableCheckedColums');
		$user = User::find(auth('site')->user()->id);
		$user->contract_colums = $colums;
		return $user->save();
	}
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function setUserDeps($request) {
		$sortedDeps = $request->get('sortedDeps');
		$user = User::find(auth('site')->user()->id);
		$user->contract_deps = $sortedDeps;
		return $user->save();
	}
	
	
	
	
	
	
	
	/**
	 * Получить список подборок. Если не передан ID договора - вернуть с учетом уже выбранных подборок
	 * @param 
	 * @return 
	 */
	public function getSelectionsToChoose($contractId = null) {
		$userId = auth('site')->user()->id;

		$choosedSelections = [];
		
		if ($contractId) {
				$contract = ContractModel::where('id', $contractId)
				->with(['selections' => function ($query) use($userId) {
					$query->where('account_id', $userId)
						->orWhereJsonContains('subscribed', $userId);
				}])
				->first();
			
			$choosedSelections = $contract->selections->pluck('id')->toArray() ?? [];
		}
		
		$allSelections = Selection::toChoose()->get();
		
		return $allSelections->map(function($item) use($choosedSelections) {
				return [
					'id' 		=> $item['id'],
					'title' 	=> $item['title'],
					'choosed'	=> in_array($item['id'], $choosedSelections)
				];
			})->values()->toArray();
	}
	
	
	
	
	
	
	/**
	 * Получить комментарии из ячейки договора
	 * @param 
	 * @return 
	 */
	public function getCellComment($params = []): string|null {
		$params['account_id'] = auth('site')->user()->id;
		$row = ContractCellComment::where($params)->first();
		return $row?->comment;
	}
	
	/**
	 * Обновить комментарии из ячейки договора
	 * @param 
	 * @return 
	 */
	public function setCellComment($params = []): bool {
		$comment = $params['comment'];
		unset($params['comment']);
		
		$params['account_id'] = auth('site')->user()->id;
		
		$row = ContractCellComment::firstOrNew($params);
		
		if ($row && !$comment) return $row->delete();
		
		$row->comment = $comment;
		
		return $row->save();
	}
	
	
	
	
	
	
	
	
	
}