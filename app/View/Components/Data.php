<?php namespace App\View\Components;

use Illuminate\View\Component;

class Data extends Component {
    
	// Этот компонент нужен для того, чтобы прокидывать какие-то общие данные, например в списки CRUD
	// он просто работает как обертка
    
	/**
     * Create a new component instance.
     * @param mixed  $data
     * @return void
     */
    public function __construct() {
        
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