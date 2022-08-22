<?php namespace App\Services\Business;

use App\Http\Filters\ContractFilter;
use App\Models\Contract as ContractModel;
use App\Models\ContractData;
use App\Models\Department;
use App\Services\Business\Department as DepartmentService;
use App\Services\DateTime;
use App\Traits\Settingable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class Contract {
	use Settingable; 
	
	private $datetime;
	private $department;
	
	public function __construct(DateTime $datetime, DepartmentService $department) {
		$this->datetime = $datetime;
		$this->department = $department;
	}
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function get(Request $request) {
		
		$queryParams = $request->only([
			'id',
			'contract_id',
			'department_id',
			'archive',
			'show'
		]);
		
		
		$filter = app()->make(ContractFilter::class, compact('queryParams'));
		$data = ContractModel::filter($filter)->get();
		return $data->mapWithKeysMany(function($item) {
			return [$item['id'] => [
				'id'				=> $item['id'] ?? null,
				'title'				=> $item['title'] ?? null,
				'titul' 			=> $item['titul'] ?? null,
				'contract' 			=> $item['contract'] ?? null,
				'subcontracting' 	=> $item['subcontracting'] ?? null,
				'customer' 			=> $item['customer'] ?? null,
				'locality' 			=> $item['locality'] ?? null,
				'price' 			=> $item['price'] ?? null,
				'date_start' 		=> $item['date_start'] ?? null,
				'date_end' 			=> $item['date_end'] ?? null,
				'hoz_method' 		=> $item['hoz_method'] ?? null,
				'type' 				=> $item['type'] ?? null,
				'contractor' 		=> $item['contractor'] ?? null,
				'archive' 			=> $item['archive'] ?? null,
				'created_a' 		=> $item['created_a'] ?? null,
				'updated_at' 		=> $item['updated_at'] ?? null,
				'object_id' 		=> $item['object_id'] ?? null
			]];
		});
	}
	
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function getWithDepartments(Request $request) {
		$filter = app()->make(ContractFilter::class, ['queryParams' => $request->except(['sort_field', 'sort_order'])]);
		
		$sortField = $request->get('sort_field', 'id');
		$sortOrder = $request->get('sort_order', 'asc');
		
		
		$onlyAssignedContractsIds = match (true) {
			$this->department->checkShowOnlyAssigned() => ContractData::select('contract_id')->where([
				'data' 	=> auth('site')->user()->id,
				'type'	=> 3
			])->get()->pluck('contract_id'),
			default => false
		};
		
		
		$data = ContractModel::filter($filter)
			->withCount(['departments as has_deps_to_send' => function (Builder $query) {
				$query->where(['show' => 0, 'hide' => 0]);
			}, 'departments as hide_count' => function (Builder $query) {
				$query->where('hide', 1);
			}])
			->with('departments')
			->when($onlyAssignedContractsIds, function ($query) use($onlyAssignedContractsIds) {
				return $query->whereIn('id', $onlyAssignedContractsIds);
			})
			->orderBy($sortField, $sortOrder)
			->get();
		
		
		$deadlinesContracts = $this->getSettings('contracts-deadlines');
		$deadlinesSteps = $this->getSettings('steps-deadlines');
		
		return $data->mapWithKeysMany(function($item) use($deadlinesContracts, $deadlinesSteps) {
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
				'title'				=> $item['title'] ?? null,
				'titul' 			=> $item['titul'] ?? null,
				'contract' 			=> $item['contract'] ?? null,
				'subcontracting' 	=> $item['subcontracting'] ?? null,
				'customer' 			=> $item['customer'] ?? null,
				'locality' 			=> $item['locality'] ?? null,
				'price' 			=> $item['price'] ?? null,
				'date_start' 		=> $item['date_start'] ?? null,
				'date_end' 			=> $item['date_end'] ?? null,
				'hoz_method' 		=> $item['hoz_method'] ?? null,
				'type' 				=> $item['type'] ?? null,
				'contractor' 		=> $item['contractor'] ?? null,
				'archive' 			=> $item['archive'] ?? null,
				'created_a' 		=> $item['created_a'] ?? null,
				'updated_at' 		=> $item['updated_at'] ?? null,
				'object_id' 		=> $item['object_id'] ?? null,
				'color' 			=> $color ?? null,
				'name' 				=> $name ?? '',
				'has_deps_to_send'	=> !!$item['has_deps_to_send'] ?? null,
				'ready_to_archive'	=> $item['hide_count'] != 0 && $item['hide_count'] == $item->departments->count(),
				
				'departments' 		=> $departments
				
			]];
		});
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
	
	
	
	
}