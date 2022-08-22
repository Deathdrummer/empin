<?php namespace App\Http\Controllers\site;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractData;
use App\Models\Department;
use App\Services\Business\Department as DepartmentService;
use App\Services\Business\Contract as ContractService;
use App\Traits\Renderable;
use App\Traits\Settingable;
use Illuminate\Http\Request;

class Contracts extends Controller {
	use Renderable, Settingable;
	
	protected $renderPath = 'site.section.contracts.render';
	protected $data = [];
	protected $department;
	//protected $contracts;
	
	
	public function __construct(DepartmentService $department, ContractService $contract) {
		$this->department = $department;
		$this->contract = $contract;
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
		
		//logger($list->toArray());
		
		if ($list->isEmpty()) return $this->render('list');
		
		$alldeps = $this->department->getWithSteps($request);
		
		$this->_addDepsUsersToData($alldeps);
		
		$contractdata = $this->contract->buildData($list->keys());
		
		
		
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
			'setting'	=> 'contract-locality:localities',
			'key'		=> 'id',
			'value'		=> 'name'
		]]);
		
		
		$canEditAll = auth('site')->user()->can('dostup-ko-vsem-otdelam:site');
		$isArchive = $request->has('archive') && $request->get('archive') == 1;
		$isDepartment = $request->has('department_id');
		$departmentId = $request->get('department_id');
		
		$edited = ($canEditAll && !$isArchive) || $isDepartment;
		
		$sortField = $request->get('sort_field', 'id');
		$sortOrder = $request->get('sort_order', 'asc');
		
		return $this->render('list', compact('list', 'alldeps', 'contractdata', 'edited', 'departmentId', 'isArchive', 'sortField', 'sortOrder'));
	}
	
	
	
	
	
	
	
	
	
	/**
	 * @param Request  $request
	 * @return 
	 */
	public function departments(Request $request) {
		$departments = $this->department->getToSend($request);
		return $this->render('departments', compact('departments'));
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
			'contractId' 	=> $contractId,
			'departmentId' 	=> $departmentId
		] = $request->validate([
			'contractId' 	=> 'required|numeric',
			'departmentId' 	=> 'required|numeric'
		]);
		
		$contract = Contract::find($contractId);
		$statData = $contract->departments()->syncWithoutDetaching([$departmentId => ['hide' => 1]]);

		return response()->json($statData['updated']);
	}
	
	
	
	
	
	
	
	/**
	 * @param  Request $request
	 * @param  int  $id 
	 * @return 
	 */
	public function to_archive(Request $request) {
		['contractId' => $contractId] = $request->validate(['contractId' => 'required|numeric']);
		$contract = Contract::find($contractId);
		$contract->archive = 1;
		$stat = $contract->save();
		return response()->json($stat);
	}
	
	
	
	
	
	/**
	 * @param  Request $request
	 * @param  int  $id 
	 * @return 
	 */
	public function send(Request $request) {
		[
			'contractId' 	=> $contractId,
			'departmentId' 	=> $departmentId
		] = $request->validate([
			'contractId' 	=> 'required|numeric',
			'departmentId' 	=> 'required|numeric'
		]);
		
		$contract = Contract::find($contractId);
		$statData = $contract->departments()->syncWithoutDetaching([$departmentId => ['show' => 1, 'updated_show' => now()->setTime(0, 0, 0)]]);
		
		return response()->json($statData['updated']);
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