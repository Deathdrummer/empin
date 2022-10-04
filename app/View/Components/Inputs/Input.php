<?php namespace App\View\Components\Inputs;

use Illuminate\View\Component;
use App\Traits\HasComponent;

class Input extends Component {
	use HasComponent;
	
	public $type;
	public $name;
	public $value;
	public $placeholder;
	
	public $iconActionFunc; // Функция экшена иконки
	public $iconActionParams;
	
	public $tag;
	public $tagParam;
	
	
	
	/**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
		string $type = 'text',
		string $name = '',
		?string $value = null,
		?string $placeholder = null,
		/*, ?string $group = null*/
		?string $action = null,
		?string $iconaction = null,
		?string $tag = null,
	) {
        $dataTypes = collect([
			'text' 		=> 'текст',
			'password' 	=> 'пароль',
			'number' 	=> 'число', 'email' => 'E-mail',
			'search' 	=> '',
			'date' 		=> 'дату',
			'tel' 		=> 'номер телефона',
			'url' 		=> 'ссылку',
			'color' 	=> 'цвет'
		]);
		
		$this->type = $dataTypes->has($type) !== false ? $type : 'text';
        $this->name = $name;
        $this->value = htmlspecialchars_decode($value, ENT_QUOTES|ENT_HTML5);
        $this->placeholder = $placeholder ?? __('auth.typing').' '.$dataTypes->get($type);
        
		[$t, $tValue] = $this->buildTag($tag);
		$this->tag = $tValue ? $t.'="'.$tValue.'"' : $t;
        
        $this->setAction($action);
        
		if ($iconaction) {
			['function' => $iconActionFunc, 'params' => $iconActionParams] = $this->setAction($iconaction, true);
			$this->iconActionFunc = $iconActionFunc ?? null;
			$this->iconActionParams = $iconActionParams ?? null;
		}
		
    }
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function setValue($value = false, $settings = false, $setting = false) {
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
        return view('components.inputs.input');
    }
}