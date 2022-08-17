<?php namespace App\View\Components\Inputs;

use App\Traits\HasComponent;
use Illuminate\Support\Arr;
use Illuminate\View\Component;

class Select extends Component {
	use HasComponent;
	
	public $name;
	public $options;
	public $choose;
	public $empty;
	public $hasActive;
	public $chooseEmpty; // можно ли выбрать пустое значение
	public $emptyHasValue; // если выбрано значение - то пустое все равно отображается или наоборот
	
	
	
	
	/**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(string $name = '', mixed $options = false, string $choose = 'Выбрать...', string $empty = null, $chooseEmpty = null, $emptyHasValue = null, ?string $action = null) {
        $this->name = $name;
        
        $this->choose = $choose;
        $this->empty = $empty ?: __('ui.no_data');
		$this->chooseEmpty = isset($chooseEmpty);
		$this->emptyHasValue = isset($emptyHasValue);
		$this->hasActive = collect($options)->contains(function ($item, $key) {
			return isset($item['active']) && ($item['active'] === 1 || $item['active'] === true);
		});
		
		$ops = [];
		
		if ($options && is_array($options)) {
			if (Arr::isAssoc($options)) {
				foreach ($options as $value => $title) {
					$ops[] = [
						'value' => htmlspecialchars_decode($value, ENT_QUOTES|ENT_HTML5),
						'title' => htmlspecialchars_decode($title, ENT_QUOTES|ENT_HTML5),
					];
				}
			} elseif (gettype(reset($options)) === 'array') {
				foreach ($options as $item) {
					$value = isset($item['value']) ? htmlspecialchars_decode($item['value'], ENT_QUOTES|ENT_HTML5) : null;
					$title = isset($item['title']) ? htmlspecialchars_decode($item['title'], ENT_QUOTES|ENT_HTML5) : null;
					
					$ops[] = [
						'value' 	=> $value ?? $title,
						'title' 	=> $title ?? $value,
						'active' 	=> $item['active'] ?? null,
						'disabled' 	=> $item['disabled'] ?? null,
					];
				}
			} else {
				foreach ($options as $item) {
					$ops[] = [
						'value' => htmlspecialchars_decode($item, ENT_QUOTES|ENT_HTML5),
						'title' => htmlspecialchars_decode($item, ENT_QUOTES|ENT_HTML5),
					];
				}
			}
		}
		
		
		$this->options = $ops;
		
		$this->setAction($action);
    }
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function setSelected($value = false, $settings = false, $setting = false) {
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
        return view('components.inputs.select');
    }
}