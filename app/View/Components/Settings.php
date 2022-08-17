<?php namespace App\View\Components;

use App\Services\Settings as SettingsService;
use Illuminate\Http\Request;
use Illuminate\View\Component;

class Settings extends Component {
    
    public $settings;
	
	
	/**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(Request $request, SettingsService $settings) {
		$this->settings = $settings->getGroup($request->section);
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