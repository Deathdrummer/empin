<?php namespace App\Http\Middleware;

use App\Services\Locale;
use Closure;
use Illuminate\Http\Request;

class Lang {
	
	protected $locale;
	
	public function __construct(Locale $locale) {
		$this->locale = $locale;	
	}
	
	
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next) {
		$this->locale->set('admin');
        return $next($request);
    }
}