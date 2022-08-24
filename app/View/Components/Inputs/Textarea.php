<?php namespace App\View\Components\inputs;

use Illuminate\View\Component;
use App\Traits\HasComponent;

class Textarea extends Component {
    use HasComponent;
    
    public $name;
    public $value;
    public $placeholder;
    //public $group;
    
	public $tag;
	public $tagParam;
    
    
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
		string $name = '',
		?string $value = null,
		?string $placeholder = null,
		/*, ?string $group = null*/
		?string $action = null,
		?string $tag = null
	) {
        $this->name = $name;
        $this->value = htmlspecialchars_decode($value, ENT_QUOTES|ENT_HTML5);
        $this->placeholder = $placeholder ?: __('auth.typing').' текст';
        //$this->group = $group;
        
		$this->setAction($action);
		
		[$t, $tValue] = $this->buildTag($tag);
		$this->tag = $t ?? null;
		$this->tagParam = $tValue ?? null;
    }
    
    
    
    
    
    /**
     * @param 
     * @return 
     */
    public function setValue($value = null, $settings = null, $setting = null) {
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
        return view('components.inputs.textarea');
    }
}