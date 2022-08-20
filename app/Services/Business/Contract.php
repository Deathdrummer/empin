<?php namespace App\Services\Business;

use App\Http\Filters\ContractFilter;
use App\Models\Contract as ContractModel;
use App\Models\ContractData;
use App\Traits\Settingable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class Contract {
	use Settingable; 
	
	
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
		
		$data = ContractModel::filter($filter)
			->withCount(['departments as has_deps_to_send' => function (Builder $query) {
				$query->where(['show' => 0, 'hide' => 0]);
			}, 'departments as hide_count' => function (Builder $query) {
				$query->where('hide', 1);
			}])
			->with('departments')
			->orderBy($sortField, $sortOrder)
			->get();
		
		
		
		$settingsData = $this->getSettings('contracts-deadlines:deadlines', 'group:many');
		logger($settingsData);
		
		
		return $data->mapWithKeysMany(function($item) {
			$color = null;
			
			
			if (!$item['date_end']) {
				$color = '#0f0';
			} else {
				$carbon = new Carbon;
				$dateNow = $carbon->now()->setTime(0, 0, 0);
				$dateEnd = $carbon->create($item['date_end']);
				$color = match (true) {
					$dateNow <= $dateEnd->subMonths(2) => '#91f191',
					$dateNow > $dateEnd->subMonths(2) && $dateNow <= $dateEnd->subMonths(1) => '#f7e858',
					$dateNow > $dateEnd->subMonths(1) => '#ffacac',
					default => 'transparent'
				};
			}
			
			
			$departments = $item->departments->mapWithKeys(function($dep) {
				
				if ($dep['pivot']['steps']) {
					foreach ($dep['pivot']['steps'] as $stepId => $step) {
						$carbon = new Carbon;
						$dateNow = $carbon->now()->setTime(0, 0, 0);
						$dateEnd = $carbon->create($step['deadline']);
						
					}
				}
				
				
				
				
				return [$dep['id'] => [
					'id' 				=> $dep['id'] ?? null,
					'name' 				=> $dep['name'] ?? null,
					'assigned_primary'	=> $dep['assigned_primary'] ?? null,
					'show'				=> $dep['pivot']['show'] ?? null,
					'hide'				=> $dep['pivot']['hide'] ?? null,
					'steps'				=> $dep['pivot']['steps'] ?? null,
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
				'color' 			=> $color,
				'has_deps_to_send'	=> $item['has_deps_to_send'] ?? null,
				'ready_to_archive'	=> $item['hide_count'] != 0 && $item['hide_count'] == $item->departments->count(),
				
				'departments' 		=> $departments
				
			]];
		});
	}
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function buildData() {
		$cdata = ContractData::all();
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