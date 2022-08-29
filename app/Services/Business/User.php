<?php namespace App\Services\Business;


use App\Models\User as Usermodel;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class User {
	
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
		$pinStat = $request->input('stat', 1);
		$userContracts = Usermodel::find(auth('site')->user()->id)->contracts();
		$stat = $userContracts->sync([$contractId => ['pinned' => $pinStat]], false);
		return $stat;
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