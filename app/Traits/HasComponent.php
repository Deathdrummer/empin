<?php namespace App\Traits;


trait HasComponent {
	
	public $actionFunc; // Функция экшена
	public $actionParams;
	
	/**
	 * @param 
	 * @return 
	 */
	public function setAction($actionStr = null) {
		if (!$actionStr) return false;
		$actData = explode(':', $actionStr);
        $this->actionFunc = array_shift($actData) ?? null;
        $params = implode(':', $actData) ?? null;
        
        $paramsStrData = [];
        if (isset($params)) {
            foreach (explode(',', $params) as $param) {
                $param = trim($param);
                if ($param == '') $param = 'null';
                $paramsStrData[] = (is_numeric($param) || in_array($param, ['null', 'false', 'true'])) ? $param : "'".$param."'";
            }
        }
        $this->actionParams = $paramsStrData ? implode(', ', $paramsStrData) : null;
	}
	
	
}
