<?php namespace App\View\Components\Inputs;

use Illuminate\View\Component;

class InputGroup extends Component {
    
    public $groupWrap;
    public $nameGroup;
    
    
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(?string $group = null, ?string $name = null) {
        $this->groupWrap = $group;
        $this->nameGroup = $name;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render() {
		return <<<'blade'
			{{$slot}}
		blade;
    }
}