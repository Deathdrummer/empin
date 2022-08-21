<?php namespace App\Services;

use Carbon\Carbon;

class DateTime {
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function checkDiapason($datePoint = null, $conditions = null, $map = null, $returnFields = null) {
		if (is_array($conditions)) $conditions = collect($conditions);
		
		$conditions = $conditions->mapToGroups(function($item) {
			$key = isset($item['min_sign']) && $item['min_sign'] == '=' ? 'first' : 'second';
			return [$key => $item];
		});
		
		if (isset($conditions['first'])) $allConditions = $conditions['first']->merge($conditions['second']);
		else $allConditions = $conditions['second'];
		
		foreach ($allConditions->toArray() as $condition) {
			$minSign = $condition[$map['minSign']] ?? null;
			$minDateCount = $condition[$map['minDateCount']] ?? null;
			$minDateType = $condition[$map['minDateType']] ?? null;
			
			$maxSign = $condition[$map['maxSign']] ?? null;
			$maxDateCount = $condition[$map['maxDateCount']] ?? null;
			$maxDateType = $condition[$map['maxDateType']] ?? null;
			
			$dateMinPoint = $this->_getDatePoint($datePoint, $minDateType, $minDateCount); //true, false or null
			$dateMaxPoint = $this->_getDatePoint($datePoint, $maxDateType, $maxDateCount); //true, false or null
			
			$minCondition = $this->_getCondition($minSign, $dateMinPoint, [1, 2, 3]);
			$maxCondition = $this->_getCondition($maxSign, $dateMaxPoint, [4, 5]);
			
			//logger(gettype($minCondition).':'.($minCondition ? 'true' : 'false').' '.gettype($maxCondition).':'.($maxCondition ? 'true' : 'false'));
			//---------------------
			
			
			if ($minSign == '=' && $minCondition) return $this->_buildResponse($condition, $returnFields);
			
			
			if (!is_null($maxCondition) && !is_null($minCondition)) {
				if ($maxCondition && $maxCondition) return $this->_buildResponse($condition, $returnFields);
			}
			
			if (!is_null($maxCondition) && is_null($minCondition)) {
				if ($maxCondition) return $this->_buildResponse($condition, $returnFields);
			}
			
			if (is_null($maxCondition) && !is_null($minCondition)) {
				if ($minCondition) return $this->_buildResponse($condition, $returnFields);
			}
		}
		
		return array_combine($returnFields, array_fill(0, count($returnFields), null)) ;
	}
	
	
	
	
	
	
	
	
	
	
	//-------------------------------------------------------------------------------------
	
	
	
	/**
	 * @param 
	 * @return bool|null
	 */
	private function _getDatePoint($date = null, $dateType = null, $dateCount = null) {
		if (!$date || !$dateType || $dateCount === null) return null;
		$datePoint = Carbon::create($date);
		return match ($dateType) {
			'h' => $datePoint->subHours($dateCount),
			'd' => $datePoint->subDays($dateCount),
			'w' => $datePoint->subWeeks($dateCount),
			'm' => $datePoint->subMonths($dateCount),
			'y' => $datePoint->subYears($dateCount),
			default => false
		};
	}
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	private function _getCondition($sign = null, $datePoint = null, $expected = []) {
		if (!$sign || !$datePoint) return null;
		$dateNow = Carbon::now()->setTime(0, 0, 0);
		$result = match (true) {
			$sign == '>' && $dateNow > $datePoint => 1,
			$sign == '=' && $dateNow == $datePoint => 2,
			$sign == '>=' && $dateNow >= $datePoint => 3,
			$sign == '<' && $dateNow < $datePoint => 4,
			$sign == '<=' && $dateNow <= $datePoint => 5,
			default => false
		};
		if ($result === false) return false;
		return in_array($result, $expected);
	}
	
	
	
	
	/**
	 * @param 
	 * @return array|string
	 */
	private function _buildResponse($condition = null, $returnFields = null, $oneField = false) {
		if (!$condition || !$returnFields)  throw new \Exception('DateTime -> _buildResponse не переданы параметры');
		if (!is_array($returnFields)) $returnFields = pregSplit($returnFields);
		
		$returnData = [];
		foreach ($returnFields as $field) {
			if (!isset($condition[$field])) $returnData[$field] = null;
			else $returnData[$field] = $condition[$field];
		}
		
		if ($oneField && count($returnData) == 1) return reset($returnData);
		return $returnData;
	}
}