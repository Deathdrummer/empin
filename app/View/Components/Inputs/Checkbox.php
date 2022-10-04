<?php namespace App\View\Components\Inputs;

use Illuminate\View\Component;
use App\Traits\HasComponent;

class Checkbox extends Component {
	use HasComponent;
	
	public $name;
	public $label;
	public $checked;
	
	public $tag;
	public $tagParam;
    
	
	
	/**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
		string $name = '',
		?string $label = null,
		?bool $checked = null,
		?string $action = null,
		?string $tag = null
	) {
        $this->name = $name;
        $this->label = htmlspecialchars_decode($label, ENT_QUOTES|ENT_HTML5);
        $this->checked = $checked;
        
        
        $this->setAction($action);
		
		[$t, $tValue] = $this->buildTag($tag);
		$this->tag = $tValue ? $t.'="'.$tValue.'"' : $t;
    }
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function setChecked($value = false, $settings = false, $setting = false) {
		if ($value) return $value;
		if (!$setting || !$settings) return false;
		return data_get($settings, $setting);
	}
	
	
	
	
	

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render() {
        return view('components.inputs.checkbox');
    }
}