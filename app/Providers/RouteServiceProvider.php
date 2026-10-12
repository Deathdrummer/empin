<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider {
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/';
	
	
	/**
	 * DDR условная переадресация по гуарду	
	 * @param 
	 * @return 
	 */
	public static function getRedirPath($guard = null) :string {
		return $guard ?: '';
	}

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot() {
        $this->configureRateLimiting(); // это если устанавливать ограничения по запросам

        $this->routes(function () {
			Route::middleware('api')
                ->prefix('api')
                ->group([
					base_path('routes/api/api.php')
				]);
			
			Route::middleware('admin')
				->prefix('admin')
                ->group([
					base_path('routes/web/admin.php'),
				]);
			
			Route::middleware('site')
                ->group([
					base_path('routes/web/site.php'),
					base_path('routes/web/log.php')
				]);
			
			Route::middleware('ajax')
				->prefix('ajax')
                ->group([
					base_path('routes/ajax.php')
				]);
        });
        
        // Определить связывание модели и маршрута, фильтры шаблонов и т.д.
		/*Route::resourceVerbs([
			'create' => 'crear',
			'edit' => 'editar',
		]);*/
    }

    /**
	 * Настроить ограничители частоты запросов для приложения.
	 *
	 * @return void
	 */
	protected function configureRateLimiting() {
	    // Счётчик у каждого пользователя свой. Без by() ключ лимита общий (md5('api')),
	    // и 30 запросов в минуту делили между собой все пользователи мобильного приложения.
	    // Лимит входа задан здесь же, а не в маршрутах, чтобы не зависеть от route:cache.
	    RateLimiter::for('api', function (Request $request) {
	        if ($request->is('api/auth/login')) {
	            // Подбор пароля: 10 попыток в минуту на адрес с одного IP, 60 с одного IP всего
	            $email = $request->input('email');
	            $email = is_string($email) ? mb_strtolower(trim($email)) : '';

	            return [
	                Limit::perMinute(10)->by('login:' . $email . '|' . $request->ip()),
	                Limit::perMinute(60)->by('login-ip:' . $request->ip()),
	            ];
	        }

	        $userId = $request->user('sanctum')?->getAuthIdentifier();

	        return Limit::perMinute(300)->by($userId ? 'user:' . $userId : 'ip:' . $request->ip());
	    });
		
		/* RateLimiter::for('global', function (Request $request) {
	        return Limit::perMinutes(1, 1);
	    });
	    
	    RateLimiter::for('login', function (Request $request) {
			return [
				Limit::perMinute(10),
				Limit::perMinute(3)->by($request->input('email')),
			];
		}); */
	}
}