<?php namespace App\Services\Business;

use App\Models\Department as DepartmentModel;
use App\Http\Filters\DepartmentFilter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class Department {
	
	
	
	/**
	 * @param Request  $request
	 * @return 
	 */
	public function getToSend(Request $request, $sort = 'ASC') {
		if (!$contractId = $request->get('contract_id')) return false;
		
		return DepartmentModel::whereHas('contracts', function (Builder $query) use($contractId) {
			$query->where('contract_id', $contractId);
			$query->where('show', 0);
		})->get();
		
		
		/* return DepartmentModel::whereRelation('contracts', function (Builder $query) use($contractId) {
				$query->where('contract_id', $contractId);
				$query->where('show', 0);
			})
			->orderBy('_sort', $sort)
			->get(); */
	}
	
	
	
	
	
	
	
	
	
	/**
	 * @param Request  $request
	 * @return 
	 */
	public function get(Request $request, $sort = 'ASC') {
		$queryParams = $request->only([
			'id',
			'department_id',
			'show',
			'hide'
		]);
		$filter = app()->make(DepartmentFilter::class, compact('queryParams'));
		return DepartmentModel::filter($filter)->orderBy('_sort', $sort)->get();
	}
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function getWithSteps(Request $request) {
		$queryParams = $request->only([
			'department_id'
		]);
		$filter = app()->make(DepartmentFilter::class, compact('queryParams'));
		return DepartmentModel::filter($filter)->with(['steps' => function($query) {
				$query->orderBy('_sort', 'ASC');
			}])
			->orderBy('_sort', 'ASC')
			->get();
	}
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function checkShowOnlyAssigned() {
		if (auth('site')->user()->can('show-all-departments-to-assigned:site')) return false;
		$result = DepartmentModel::select('show_only_assigned')->where('id', auth('site')->user()->department_id)->first();
		return !!$result['show_only_assigned'] ?? false;
	}
	
	
	
	
	
	
	
	/**
	 * @param Object|Array  $depsData
	 * @param Array  $queryParams
	 * @param Bool  $userFields вернуть поля пользователя
	 * @return 
	 */
	public function getUsersToAssign($depsData, $userFields = false) {
		$depsIds = [];
		
		if (gettype($depsData) == 'object') {
			$depsIds = $depsData->map(function ($item) {
				if ($item['steps']->where('type', 3)->count()) return $item['id'];
			})->whereNotNull()->values();
			
		} elseif (gettype($depsData) == 'array') {
			$depsIds = $depsData;
		}
		
		if (empty($depsIds)) return [];
		
		$depsUsers = User::whereIn('department_id', $depsIds)
			->get()
			->mapWithKeysMany(function ($item, $key) use($userFields) {
				return [$item['department_id'] => [
						$item['id'] => $this->_buildUserArray($item, $userFields)
					]
				];
			});
		
		return $depsUsers;
	}
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	private function _buildUserArray($row = null, $fields = null) {
		if (!$row || !$fields) return [];
		
		if (is_string($fields)) return $row[$fields]; 
		
		$data = [];
		foreach($fields as $field) {
			$data[$field] = $row[$field];
		}
		return $data; 
	}
	
	
}