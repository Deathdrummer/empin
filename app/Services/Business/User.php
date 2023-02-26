<?php namespace App\Services\Business;


use App\Models\User as Usermodel;
use App\Models\ContractCellComment as ContractCellCommentModel;
use App\Models\Contract as Contractmodel;
use App\Models\ContractSelectedColor;
use App\Services\Settings;
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
		$stat = $request->input('stat');
		$contractsIds = $request->input('contracts_ids');
		
		$contractsIds = match($stat) {
			-1	=> array_keys($contractsIds),
			0	=> array_keys(array_filter($contractsIds, fn($v) => $v === false)),
			1	=> array_keys($contractsIds),
		};
		
		$pinStat = match($stat) {
			-1	=> 1,
			0	=> 1,
			1	=> 0,
		};
		
		if (!$contractsIds) return false;
		
		$syncData = [];
		foreach ($contractsIds as $contractId) {
			$syncData[$contractId] = ['pinned' => $pinStat];
		}
		
		$userId = auth('site')->user()->id;
		$userContracts = Usermodel::find($userId)->contracts();
		$stat = $userContracts->sync($syncData, false);
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
	
	
	
	
	
	
	
	
	/** Получить комменатрий ячейки
	 * @param 
	 * @return 
	 */
	public function getCellComment($params = []) {
		$params['account_id'] = $params['account_id'] ?? auth('site')->user()->id;
		$row = ContractCellCommentModel::where($params)->first();
		return $row?->comment;
	}
	
	
	
	
	
	/** Получить все комментарии всех ячеек указанных договоров
	 * @param [account_id, contract_id, department_id] contract_id может быть массивом
	 * @return 
	 */
	public function getCellComments($params = []) {
		$params['account_id'] = $params['account_id'] ?? auth('site')->user()->id;
		$commentsData = ContractCellCommentModel::when($params, function($query) use($params) {
			$query->where('account_id', $params['account_id']);
			if (isset($params['contract_id'])) $query->whereIn('contract_id', (array)$params['contract_id']);
			if (isset($params['department_id'])) $query->where('department_id', $params['department_id']);
		})->get();
		
		
		if ($commentsData->isEmpty()) return false;
		
		$grouped = $commentsData->mapWithKeys(function ($item, $key) {
			return [$item['contract_id'] => [$item['department_id'] =>[$item['step_id'] => true]]];
		});
		
		return $grouped?->toArray() ?? false;
	}
	
	
	
	
	/** Задать комментарий ячейки
	 * @param 
	 * @return 
	 */
	public function setCellComment($params = []) {
		$comment = $params['comment'];
		unset($params['comment']);
		
		$params['account_id'] = $params['account_id'] ?? auth('site')->user()->id;
		
		$row = ContractCellCommentModel::firstOrNew($params);
		
		if ($row && !$comment) return $row->delete();
		
		$row->comment = $comment;
		
		return $row->save();
	}
	
	
	
	
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function getContractsColors($contractsIds = null) {
		$accountId = auth('site')->user()->id;
		
		$contractsColors = ContractSelectedColor::where('account_id', $accountId)
			->when($contractsIds, function($query) use($contractsIds) {
				if (!is_array($contractsIds)) $query->where('contract_id', $contractsIds);
				else $query->whereIn('contract_id', $contractsIds);
			})
			->get();
		
		if ($contractsColors->isEmpty()) return false;
		
		$userContractColors = $contractsColors?->pluck('color_id', 'contract_id')?->toArray();
		
		$settings = app()->make(Settings::class);
		$contractSelectionColors = $settings->get('contract-selection-colors')?->pluck('color', 'id')?->toArray();
		
		$resData = [];
		foreach ($userContractColors as $contractId => $colorId) {
			$resData[$contractId] = $contractSelectionColors[$colorId] ?? null;
		}
		
		return $resData;
	}
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function setContractColor($contractIds = [], $colorId = null) {
		$accountId = auth('site')->user()->id;
		
		if (is_null($colorId)) {
			ContractSelectedColor::whereIn('contract_id', $contractIds)->where('account_id', $accountId)->delete();
			return null;
		} else {
			foreach ($contractIds as $contractId) {
				$contractSelectedColor = ContractSelectedColor::firstOrCreate([
					'contract_id' => $contractId,
					'account_id' => $accountId,
				], [
					'contract_id' => $contractId,
					'account_id' => $accountId,
				]);
				
				$contractSelectedColor->color_id = $colorId;
				$stat = $contractSelectedColor->save();
				if (!$stat) return false;
			}
			
			$settings = app()->make(Settings::class);
			$contractSelectionColors = $settings->get('contract-selection-colors')?->pluck('color', 'id')?->toArray();
			
			return $contractSelectionColors[$colorId] ?? null;
		}
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