<?php namespace App\Services\Business;


use App\Models\User as Usermodel;
use Illuminate\Http\Request;

class User {
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function getWithDepartments($depId = false, $excludeUsers = []) {
		return Usermodel::select(['id', 'name', 'pseudoname', 'department_id'])
			->when($depId, function($query) use($depId) {
				if ($depId == -1) $query->whereNull('department_id');
				else $query->where('department_id', $depId);
			})
			->when($excludeUsers, function($query) use($excludeUsers) {
				$query->whereNotIn('id', (array)$excludeUsers);
			})
			->orderBy('_sort')
			->get()
			->groupBy('department_id', false);
	}
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function getPinnedContracts() {
		if (!$userContracts = $this->_getData()) return false;
		
		return $userContracts->mapWithKeys(function($item) {
			return [$item['id'] => $item->pivot['pinned']];
		});
	}
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function getViewedContracts() {
		if (!$userContracts = $this->_getData()) return false;
		
		return $userContracts->mapWithKeys(function($item) {
			return [$item['id'] => $item->pivot['pinned']];
		});
	}
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function getContractsData() {
		$data = ['viewed' => [], 'pinned' => []];
		if (!$userContracts = $this->_getData()) return $data;
		
		foreach ($userContracts as $item) {
			$data['viewed'][$item['id']] = $item->pivot['viewed'];
			if ($item->pivot['pinned']) $data['pinned'][] = $item['id'];
		}
		
		return $data;
	}
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function checkContractAsViewed(Request $request) {
		if (!$contractId = $request->input('contract_id')) return false;
		$userContracts = Usermodel::find(auth('site')->user()->id)->contracts();
		$stat = $userContracts->sync([$contractId => ['viewed' => 1]], false);
		return $stat;
	}
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function pinContract(Request $request) {
		if (!$contractId = $request->input('contract_id')) return false;
		$pinStat = (int)$request->input('stat', 1);
		$userContracts = Usermodel::find(auth('site')->user()->id)->contracts();
		$stat = $userContracts->sync([$contractId => ['pinned' => $pinStat]], false);
		return $stat;
	}
	
	
	
	
	
	/** Получить настройки для пользователя (поле settings)
	 * @param 
	 * @return 
	 */
	public function getSettings($setting = null): mixed {
		$user = Usermodel::find(auth('site')->user()->id);
        $settings = $user->settings;
       
	    if (is_null($setting)) return $settings;
		
		$setting = data_get($settings, $setting);
        return $setting;
	}
	
	
	
	
	
	/** Задать настройки для пользователя (поле settings)
	 * @param 
	 * @return 
	 */
	public function setSetting($setting = null, $value = null): bool {
		if (is_null($setting)) return false;
		
		$user = Usermodel::find(auth('site')->user()->id);
        $settings = $user->settings;
        
		$user->settings = data_set($settings, $setting, $value);
        return $user->save();
	}
	
	
	
	
	
	
	
	
	
	
	
	//-------------------------------------------------------------------------------------
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	private function _getData() {
		return Usermodel::find(auth('site')->user()->id)->contracts;
	}
	
	
}