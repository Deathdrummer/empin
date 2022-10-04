<?php namespace App\View\Components\Inputs;

use App\Traits\HasComponent;
use Illuminate\View\Component;

class Button extends Component {
	use HasComponent;
	
	public $action;
    public $actionParams;
	
	public $tag;
	public $tagParam;
    
    
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
		?string $action = null,
		?string $tag = null
	) {
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
		
		
		[$t, $tValue] = $this->buildTag($tag);
		$this->tag = $tValue ? $t.'="'.$tValue.'"' : $t;
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
