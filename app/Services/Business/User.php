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
		$userContracts = $this->_getData();
		
		return $userContracts->mapWithKeys(function($item) {
			return [$item['id'] => $item->pivot['pinned']];
		});
	}
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function getViewedContracts() {
		$userContracts = $this->_getData();
		
		return $userContracts->mapWithKeys(function($item) {
			return [$item['id'] => $item->pivot['pinned']];
		});
	}
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function getContractsData() {
		$userContracts = $this->_getData();
		
		$data = ['viewed' => [], 'pinned' => []];
		foreach ($userContracts as $item) {
			$data['viewed'][$item['id']] = $item->pivot['viewed'];
			if ($item->pivot['pinned']) $data['pinned'][] = $item['id'];
		}
		
		return $data;
		
		// return $userContracts->mapWithKeysMany(function($item) {
		// 	
		// 	$row['viewed'] = [$item['id'] => $item->pivot['viewed']];
		// 	
		// 	return $row;
		// 	/* 	'pinned' => [$item['id'] => $item->pivot['pinned'] == 1 ?],
		// 		'viewed' => [$item['id'] => $item->pivot['viewed']],
		// 	]; */
		// });
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
	
	
	
	
	
	
	
	
	
	//-------------------------------------------------------------------------------------
	
	
	/**
	 * @param 
	 * @return 
	 */
	private function _getData() {
		return Usermodel::find(auth('site')->user()->id)->contracts;
	}
	
	
}