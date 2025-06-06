<?php namespace App\Services\Business;

use App\Models\Department as DepartmentModel;
use App\Http\Filters\DepartmentFilter;
use App\Models\ListUser;
use App\Models\Staff;
use App\Models\User;
use App\Services\Business\User as BusinessUser;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class Department {
	
	
	
	
	/**
	* Retrieves all records with the specified fields.
	*
	* @param array $fields The fields to include in the result. Defaults to ['id', 'name', 'show_only_assigned', 'sort'].
	* @return mixed
	*/
	public function getAll(array $fields = ['id', 'name', 'show_only_assigned', 'sort']) {
		// Гарантируем, что поле id всегда есть в списке
		$selectedFields = array_unique(array_merge(['id'], $fields));
		
		return DepartmentModel::select($selectedFields)
			->orderBy('_sort', 'ASC')
			->get();
	}
	
	
	
	
	
	/**
	 * @param Request  $request
	 * @return 
	 */
	public function getToSend(Request $request, $sort = 'ASC') {
		$contractId = $request->get('contractId');
		
		if (!$contractId) return DepartmentModel::all();

		$departments = DepartmentModel::whereHas('contracts', function (Builder $query) use($contractId) {
			$query->where('contract_id', $contractId);
			$query->where(function($q) {
				$q->where('show', 0);
				$q->orWhere(function($sq) {
					$sq->where('show', 1);
					$sq->where('hide', 1);
				});
			});
		})->with(['info' => function($q) use($contractId) {
			$q->select('department_id', 'hide')->where('contract_id', $contractId);
		}])->get();
		
		return $departments;
		
		
		
		/* return DepartmentModel::whereRelation('contracts', function (Builder $query) use($contractId) {
				$query->where('contract_id', $contractId);
				$query->where('show', 0);
			})
			->orderBy('_sort', $sort)
			->get(); */
	}
	
	
	
	
	
	
	
	
	
	/**
	 * @param Request  $request
	 * @param string  $sort
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
		
		
		$contractDeps = auth('site')->user()->contract_deps;
		$implodeDepsIds = $contractDeps ? implode(',', $contractDeps) : false;
		
		return DepartmentModel::filter($filter)->with(['steps' => function($query) {
				$query->orderBy('sort', 'ASC');
			}])
			->when($implodeDepsIds, function ($query) use($implodeDepsIds) {
				$query->orderByRaw("FIND_IN_SET(id, '$implodeDepsIds')");
			}, function($query) {
				$query->orderBy('sort', 'ASC');
			})
			->get();
	}
	
	
	
	
	
	
	
	/**
	 * @param Request  $request
	 * @return 
	 */
	public function getWithUsers($depId = false) {
		return DepartmentModel::with(['users' => function($query) {
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
		return isset($result['show_only_assigned']) ? !!$result['show_only_assigned'] : false;
	}
	
	
	
	
	
	
	
	/**
	 * @param Object|Array  $depsData
	 * @param Array  $queryParams
	 * @param Bool  $userFields вернуть поля пользователя
	 * @return 
	 */
	public function getUsersToAssign($depsData, $userFields = false, $registred = true, $workStat = null) {
		$depsIds = [];
		
		if (gettype($depsData) == 'object') {
			$depsIds = $depsData->map(function ($item) {
				if ($item['steps']->where('type', 3)->count()) return $item['id'];
			})->whereNotNull()->values();
			
		} elseif (gettype($depsData) == 'array') {
			$depsIds = $depsData;
		}
		
		if (empty($depsIds)) return [];
		
		$depsIds = !is_array($depsIds) ? $depsIds?->toArray() : $depsIds;
		
		$usersService = app()->make(BusinessUser::class);
		
		$depsUsers = $usersService->get(fields: ['department_id', 'full_name', 'working'], registred: $registred, departments: $depsIds, workStat: $workStat);
		
		$staffLists = ListUser::getStaffLists();
		
		$result = [];
		$staffLists->each(function ($listsIds, $staffId) use ($depsUsers, $userFields, &$result) {
			if (!$user = $depsUsers[$staffId] ?? false) return true;
			foreach ($listsIds as $lId) {
				$result[$user['department_id']][$lId][] = $this->_buildUserArray($user, $userFields);
			}
		});
		
		return $result;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	//-----------------------------------------------------------------------------------------------------------------------------------
	
	/**
	 * @param 
	 * @return 
	 */
	private function _buildUserArray($row = null, $fields = null) {
		if (!$row || !$fields) return [];
		
		if (is_string($fields)) return $row[$fields]; 
		
		$data = [];
		foreach($fields as $field) {
			if (strpos($field, ':') !== false) {
				$fd = explode(':', $field);
				$data[$fd[1] ?? $fd[0]] = $row[$fd[0]];
				continue;
			}
			$data[$field] = $row[$field];
		}
		return $data; 
	}
	
	
}