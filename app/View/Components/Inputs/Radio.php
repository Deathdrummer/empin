<?php namespace App\View\Components\Inputs;

use Illuminate\View\Component;
use App\Traits\HasComponent;

class Radio extends Component {
	use HasComponent;
	
	public $label;
	public $checked;
	public $value;
	public $current;
	
	public $tag;
	public $tagParam;
	
	
	/**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
		mixed $value = null,
		mixed $current = null,
		?string $label = null,
		?bool $checked = null,
		?string $action = null,
		?string $tag = null
	) {
        $this->label = htmlspecialchars_decode($label, ENT_QUOTES|ENT_HTML5);
		$this->checked = $checked;
		$this->value = $value;
		$this->current = $current;
		
		$this->setAction($action);
		
		[$t, $tValue] = $this->buildTag($tag);
		$this->tag = $tValue ? $t.'="'.$tValue.'"' : $t;
    }
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function isChecked($settings = false, $setting = false) {
		if (!$this->value) return false;
		if ($this->current) return $this->value == $this->current;
		if (!$setting || !$settings) return false;
		return data_get($settings, $setting) == $this->value;
	}
	
	
	

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render() {
        return view('components.inputs.radio');
    }
}