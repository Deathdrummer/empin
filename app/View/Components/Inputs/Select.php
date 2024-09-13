<?php namespace App\View\Components\Inputs;

use App\Traits\HasComponent;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Select extends Component {
	use HasComponent;
	
	public $name;
	public $options;
	public $optionsType;
	public $choose;
	public $empty;
	public $hasActive;
	public $chooseEmpty; // можно ли выбрать пустое значение
	public $emptyHasValue; // если выбрано значение - то пустое все равно отображается или наоборот
	
	public $tag;
	public $tagParam;
	
	
	/**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
		string $name = '',
		mixed $options = false,
		mixed $optionsType = null,
		array $exclude = [],
		mixed $showactive = null,
		string $choose = 'Выбрать...',
		string $empty = null,
		$chooseEmpty = null,
		$emptyHasValue = null,
		?string $action = null,
		?string $tag = null
	) {
		
        $this->name = $name;
        
        $this->choose = $choose;
        $this->empty = $empty ?: __('ui.no_data');
		$this->chooseEmpty = isset($chooseEmpty);
		$this->emptyHasValue = isset($emptyHasValue);
		$this->hasActive = collect($options)->contains(function ($item, $key) {
			return isset($item['active']) && ($item['active'] === 1 || $item['active'] === true);
		});
		
		$ops = [];
		
		$options = $options instanceof Collection ? $options?->toArray() : $options;
		
		$firstItem = arrGetFirstItem($options);
		
		
		if ($firstItem) {
			if (is_array($firstItem)) {
				foreach ($options as $item) {
					$value = isset($item['value']) ? htmlspecialchars_decode($item['value'], ENT_QUOTES|ENT_HTML5) : null;
					$title = isset($item['title']) ? htmlspecialchars_decode($item['title'], ENT_QUOTES|ENT_HTML5) : null;
					
					if (in_array($value, $exclude)) continue;	
					
					if (($value ?? $title) != $showactive && ($item['hidden'] ?? false)) continue;
					
					$ops[] = [
						'value' 	=> $value ?? $title,
						'title' 	=> $title ?? $value,
						'active' 	=> $item['active'] ?? null,
						'disabled' 	=> $item['disabled'] ?? null,
					];
				}
			} elseif (Arr::isAssoc($options)) {
				foreach ($options as $value => $title) {
					if (in_array($value, $exclude)) continue;
					$ops[] = [
						'value' => htmlspecialchars_decode($value, ENT_QUOTES|ENT_HTML5),
						'title' => htmlspecialchars_decode($title, ENT_QUOTES|ENT_HTML5),
					];
				}
			} else {
				foreach ($options as $item) {
					if (in_array($item, $exclude)) continue;
					$ops[] = [
						'value' => htmlspecialchars_decode($item, ENT_QUOTES|ENT_HTML5),
						'title' => htmlspecialchars_decode($item, ENT_QUOTES|ENT_HTML5),
					];
				}
			}
		}
		
		$this->options = $ops;
		
		$this->setAction($action);
		
		[$t, $tValue] = $this->buildTag($tag);
		$this->tag = $tValue ? $t.'="'.$tValue.'"' : $t;
    }
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function setSelected($value = false, $settings = false, $setting = false) {
		if ($value !== false) return htmlspecialchars_decode($value, ENT_QUOTES|ENT_HTML5);
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