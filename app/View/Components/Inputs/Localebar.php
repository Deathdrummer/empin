<?php namespace App\View\Components\inputs;

use App\Traits\HasComponent;
use Illuminate\Support\Facades\App;
use Illuminate\View\Component;

class Localebar extends Component {
    use HasComponent;
	
	public $locales = [];
	public $currentLocale;
	
	/**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct() {
		$localesList = config('app.locales_list');
		
		foreach ($localesList as $locale) {
			$this->locales[$locale] = __('ui.locales.'.$locale) ?? null;
		}
		
		$this->currentLocale = App::currentLocale();
		
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render() {
        return view('components.inputs.localebar');
    }
}