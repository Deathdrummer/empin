<?php namespace App\Traits;


trait HasComponent {
	
	public $actionFunc; // Функция экшена
	public $actionParams;
	
	
	/**
	 * @param string  $actionStr строка параметра action
	 * @param bool  $returnData вернуть данные, а не заносить из в переменные
	 * @return 
	 */
	public function setAction($actionStr = null, $returnData = false) {
		if (!$actionStr) return false;
		$actData = explode(':', $actionStr);
		
		if ($returnData) $actionFunc = array_shift($actData) ?? null;
        else $this->actionFunc = array_shift($actData) ?? null;


		if (!empty($actData)) {
			$params = implode(':', $actData) ?? null;
			$paramsStrData = [];
			
			foreach (explode(',', $params) as $param) {
				$param = trim($param);
				if ($param == '') $param = 'null';
				$paramsStrData[] = (is_numeric($param) || in_array($param, ['null', 'false', 'true'])) ? $param : "'".$param."'";
			}
			
			$buildedParams = $paramsStrData ? implode(', ', $paramsStrData) : null;
		}
		
		if ($returnData) return ['function' => $actionFunc ?? null, 'params' => $buildedParams ?? null];
        
		$this->actionParams = $params ?? null;
	}
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function buildTag($tag = null) {
		if (!$tag) return null;
		$tData = splitString($tag, ':');
		$t = array_shift($tData) ?? null;
		$value = implode(':', $tData) ?? null;
		return [$t, $value];
	}
	
	
	
	
	
	
	
	
}
