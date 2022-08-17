<?php namespace App\View\Components\Inputs;

use Illuminate\View\Component;

class Button extends Component {
	
	public $action;
    public $actionParams;
    
    
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(?string $action = null) {
        $actData = explode(':', $action);
        $this->action = array_shift($actData) ?? null;
        $params = implode(':', $actData) ?? null;
        
        $paramsStrData = [];
        if (isset($params) && ($splitParams = splitString($params, ','))) {
			foreach ($splitParams as $param) {
                $param = trim($param);
                if ($param == '') $param = 'null';
                $paramsStrData[] = (is_numeric($param) || in_array($param, ['null', 'false', 'true'])) ? $param : "'".$param."'";
            }
        }
        $this->actionParams = $paramsStrData ? implode(', ', $paramsStrData) : null;
    }
    
    
    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render() {
        return view('components.inputs.button');
    }
}
