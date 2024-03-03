<?php namespace App\Providers;

use App\View\Components\Card;
use App\View\Components\Data;
use App\View\Components\Inputs\Button;
use App\View\Components\Inputs\ButtonsGroup;
use App\View\Components\Inputs\Checkbox;
use App\View\Components\Inputs\Datepicker;
use App\View\Components\Inputs\File;
use App\View\Components\Inputs\Input;
use App\View\Components\Inputs\InputGroup;
use App\View\Components\Inputs\Localebar;
use App\View\Components\Inputs\Textarea;
use App\View\Components\Inputs\Radio;
use App\View\Components\Inputs\Select;
use App\View\Components\Settings;
use App\View\Components\Simplelist;
use App\View\Components\Tabs;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        //$this->app->bind(\App\Contracts\Rool::class, \App\Services\DdrService::class);
    }
	
	
	
	
	

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
		
		
		//Model::preventSilentlyDiscardingAttributes(!app()->isProduction()); // Это чтобы выдать ошибку, если попытка сохранить поле в БД, которое не прописано в fillable 
		
        // свои директивы https://laravel.su/docs/8.x/blade#extending-blade
        //Blade::directive('padej', function ($num, $variants = '') {return "<?php  код ? >";});
        
		Blade::if('verify', function ($guard = null) {
			return Auth::guard($guard)->user()->email_verified_at ?? null !== null;
		});
		
        
        // Обертки
		Blade::component('input-group', InputGroup::class);
		Blade::component('buttons-group', ButtonsGroup::class);
		Blade::component('settings', Settings::class); // Обертка для компонентов, которые принимают settings данные @aware. Например: simmplelist
		Blade::component('data', Data::class); // ??? Прокидывать данные ДЛЯ КОМПОНЕНТОВ, например в CRUD списках передавать данные для выпад. списоков
		
		// Компоненты
		Blade::component('input', Input::class);
		Blade::component('textarea', Textarea::class);
		Blade::component('checkbox', Checkbox::class);
		Blade::component('radio', Radio::class);
		Blade::component('select', Select::class);
		Blade::component('file', File::class);
		Blade::component('button', Button::class);
		Blade::component('datepicker', Datepicker::class);
		Blade::component('localebar', Localebar::class);
		Blade::component('card', Card::class);
		Blade::component('tabs', Tabs::class);
		
		Blade::component('simplelist', Simplelist::class);
		
		//Blade::component('input', 'app.components.inputs');
		
		

		
		if (Str::contains(url()->current(), '/admin')) {
				// Глобальные переменные для путей admin/*
			View::composer('admin/*', function($view) {
				$view->with([
					'authView' 				=> session('admin-auth-view', 'admin.auth.auth'),
					'adminRegister' 		=> session()->pull('admin-register', null),
					'adminLogin' 			=> session()->pull('admin-login', null),
					'adminEmailVerified' 	=> session()->pull('admin-email-verified', null),
					'adminResetPassword' 	=> session()->pull('admin-reset-password', null),
				]);
			});
		} else {
			// Глобальные переменные для путей *
			View::composer('*', function($view) {
				$view->with([
					'authView' 				=> session('site-auth-view', 'site.auth.auth'),
					'siteRegister' 			=> session()->pull('site-register', null),
					'siteLogin' 			=> session()->pull('site-login', null),
					'siteEmailVerified'		=> session()->pull('site-email-verified', null),
					'siteResetPassword'		=> session()->pull('site-reset-password', null),
				]);
			});
		}
		
		
		
		
		
		
		// Использование компоновщиков на основе классов Например, 
		// вы можете создать каталог app/View/Composers для размещения всех компоновщиков вашего приложения:
        //View::composer('profile', ProfileComposer::class);
		
		
		// Совсем глобальные переменные
		//View::share('adminResetPassword', session()->pull('admin-reset-password', null));
		
		
		
		// !!! чтобы заработало - нужно обновить версию LARAVEL
		/* DB::whenQueryingForLongerThan(500, function () {
            // срабатывает при превышении заданного порога длительности запроса к базе данных (в миллисекундах). 
			// Например, в нём можно отправить уведомление разработчику.
        }); */
		
		
		
		
		/* Response::macro('headerCyrilic', function (?array $data = null) {
			if (is_array($data)) {
				return Response::make(urlencode(json_encode($data)));
			}
			return Response::make(urlencode($data));
        }); */
		
		
		
		
		/* 
			правило : гуард
		 */
		Blade::if('cando', function ($rule = null, $data = null) {
			if (!$rule) return false;
			$explode = splitString($rule, ':');
			$permission = $explode[0] ?? false;
			$guard = $explode[1] ?? 'site';
			
			if ($guard) return Auth::guard($guard)->check() ? Auth::guard($guard)->user()->can($rule) : false;
			return Auth::check() ? Auth::user()->can($rule) : false;
		});
		
		
		
		
		
		/* 
			правило : гуард
		 */
		Blade::if('cananydo', function ($rules = null, $data = null) {
			if (!$rules) return false;
			
			if (!is_array($rules)) $rules = splitString($rules, ',');
			foreach($rules as $rule) {
				$explode = splitString($rule, ':');
				$permission = $explode[0] ?? false;
				$guard = $explode[1] ?? 'site';
				
				if ($guard) {
					if (Auth::guard($guard)->check() && Auth::guard($guard)->user()->can($rule)) return true;
				} elseif (Auth::check() && Auth::user()->can($rule)) return true;
			}
		});
		
		
		
		
		/* 
			правило : гуард
		 */
		Blade::if('canalldo', function ($rules = null, $data = null) {
			if (!$rules) return false;
			
			$stat = false;
			if (!is_array($rules)) $rules = splitString($rules, ',');
			foreach($rules as $rule) {
				$explode = splitString($rule, ':');
				$permission = $explode[0] ?? false;
				$guard = $explode[1] ?? 'site';
				
				if ($guard) {
					if (Auth::guard($guard)->check() && !Auth::guard($guard)->user()->can($rule)) return false;
				} elseif (Auth::check() && !Auth::user()->can($rule)) return false;
			}
		});
		
		
		
		
		
		//  Blade::directive('permission', function($params) {
		// 	logger('params');
		// 	logger((array)$params);
		// 	if (!$params) return "<?php if (false) : ? >";
		// 	
		// 	
		// 	return "<?php if (false) : ? >";
		// 	
		// 	[$gate, $guard] = explode(":", $params);
		// 	
		// 	if (!isset($guard)) 
		// 	
		// 	logger($gate);
		// 	logger($guard);
		// 	
		// 	
		// 	if (!$gate) return "<?php if (false) : ? >";
		// 	
		// 	 if ($guard) {
		// 		return "<?php if (Auth::guard('{$guard}')->user()->can('{$gate}')) : ? >";
		// 	} else {
		// 		return "<?php if (Auth::user()->can('{$gate}')) : ? >";
		// 	} 
        // });
		// 
		// Blade::directive('endpermissiom', function() {
		// 	return "<?php endif; ? >";
        // }); 
		
		
		
		
		
		
		
		
		
		Blade::directive('date', function ($expression) {
            return "<?php echo $expression ? Carbon\Carbon::parse($expression)->locale('ru')->isoFormat('DD MMMM YYYY', 'Do MMMM') : ''; ?>";
        });
		
		Blade::directive('time', function ($expression) {
            return "<?php echo $expression ? Carbon\Carbon::parse($expression)->format('H:i') : ''; ?>";
        });
		
		Blade::directive('number', function ($expression) {
			$d = splitString($expression, ',');
			if (!isset($d[0])) return false;
			
			$num = $d[0];
			$countAfterDot = $d[1] ?? 2;
			$dotSymbal = $d[2] ?? '\'.\'';
			$spacer = $d[3] ?? '\' \'';
			
            return "<?php echo number_format({$num}, {$countAfterDot}, {$dotSymbal}, {$spacer}); ?>";
        });
		
		Blade::directive('symbal', function ($expression) {
			$symbal = match ($expression) {
				'money' => '₽',
				default => '',
			};
			
            return "<?php echo '<sup>{$symbal}</sup>'; ?>";
        });
		
		
		
		
		
		
		
		//Blade::directive('setting', function ($key) {
        //    return "< ?php echo ($expression)->format('m/d/Y H:i'); ? >";
        //});e
		
		
		
		/**
		 * Run an associative map over each of the items.
		 *
		 * The callback should return an associative array with a single key / value pair.
		 *
		 * @template TMapWithKeysKey of array-key
		 * @template TMapWithKeysValue
		 *
		 * @param  callable(TModel, TKey): array<TMapWithKeysKey, TMapWithKeysValue>  $callback
		 * @return static<TMapWithKeysKey, TMapWithKeysValue>
		 */
		Collection::macro('mapWithKeysMany', function (callable $callback) {
			$result = [];
			
			foreach ($this->items as $key => $value) {
				$assoc = $callback($value, $key);
				foreach ($assoc as $mapKey => $mapValue) {
					if (isset($result[$mapKey])) $result[$mapKey] = array_replace_recursive($result[$mapKey], $mapValue); 
					else $result[$mapKey] = $mapValue;
				}
			}
			
			return new static($result);
		});
		
		
    }
}