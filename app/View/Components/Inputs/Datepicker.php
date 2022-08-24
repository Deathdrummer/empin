<?php namespace App\View\Components\inputs;

use App\Traits\HasComponent;
use Carbon\Carbon;
use Illuminate\View\Component;

class Datepicker extends Component {
    use HasComponent;
	
	public $name;
	public $value;
	public $placeholder;
	public $group;
	
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
		?string $group = null,
		?string $tag = null
	) {
        $this->name = $name;
        $this->value = $value;
        $this->placeholder = $placeholder ?: __('auth.choose_date');
        $this->group = $group;
		
		[$t, $tValue] = $this->buildTag($tag);
		$this->tag = $t ?? null;
		$this->tagParam = $tValue ?? null;
    }
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function setInpGroup() {
		if ($this->group) return $this->group ? "inpgroup={$this->group}" : '';
	}
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function setValue($value = null, $settings = null, $setting = null) {
		if ($value) return Carbon::parse($value)->format('Y-m-d') ;
		if (!$setting || !$settings) return false;
		$sett = data_get($settings, $setting);
		return Carbon::parse($sett)->format('Y-m-d');
	}
	
	

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render() {
        return view('components.inputs.datepicker');
    }
}