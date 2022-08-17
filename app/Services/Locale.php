<?php namespace App\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Locale {
	
	protected $guard;
	
	public function __construct(?string $guard = null) {
		$this->guard = $guard;
	}
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function set(?string $guard = null) {
		$guard = $this->guard ?: $guard;
		
		if (!$guard) {
			logger()->error('Locale -> необходимо указать guard!');
			return false;
		}
		
		$locale = match (true) {
			Auth::guard($guard)->check() => Auth::guard($guard)->user()->locale ?: config('app.locale'),
			Session::has('locale') => Session::get('locale'),
			default => false,
		};
		
		if (!$locale) {
			$locale = config('app.locale');
			Session::put('locale', $locale);
		}
		
		App::setLocale($locale);
	}
	
	
	
	
	
}