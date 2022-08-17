<?php

use App\Http\Controllers\AdminController;
use App\Http\Requests\Auth\AdminEmailVerificationRequest;
use App\Models\AdminSection;
use App\Models\AdminUser;
use App\Services\Settings;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;




// регистрация, авторизация, выход
Route::controller(AdminController::class)->middleware(['lang', 'isajax:admin'])->group(function() {
	Route::get('/reg', 'regForm')->name('admin.reg');
	Route::post('/register', 'register');
	Route::get('/auth', 'authForm')->name('admin.auth');
	Route::post('/login', 'login');
	Route::get('/logout', 'logout')->name('admin.logout');
});



// подтверждение адреса почты
/* Route::get('/email/verify', function () {
    return view('admin.auth.verify-email');
})->middleware('auth:admin')->name('admin.verification.notice'); */

Route::get('/email/verify/{id}/{hash}', function (AdminEmailVerificationRequest $request) {
	$request->fulfill();
	session(['admin-email-verified' => __('auth.email_verified')]);
	return redirect(route('admin'));
})->middleware(['lang', 'auth:admin', 'signed'])->name('admin.verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification('admin');
    return response()->json(['sending' => __('auth.email_verify_sending')]);
})->middleware(['lang', 'auth:admin', 'throttle:6,1'])->name('admin.verification.send');




// Сброс пароля
Route::get('/forgot-password', function (Request $request, Settings $settingsService) {
	$email = $request->input('email');
	
	$settings = $settingsService->getMany('company_name');
	
	return view('admin.auth.forgot-password', compact('email'), $settings->all());
})->middleware('lang', 'guest:admin')->name('password.request');

Route::post('/forgot-password', function (Request $request) {
	$request->validate(['email' => 'required|email|exists:admin_users,email']);
    $status = Password::broker('admin_users')->sendResetLink($request->only('email'), function($user, $token) {
		$user->sendPasswordResetNotification($token, 'admin');
	});
	
	if ($status === Password::RESET_LINK_SENT) {
		return response()->json(['message' => __($status)]);
	} else {
		return response()->json(['errors' => ['email' => [__($status)]]]);
	}
})->middleware(['lang', 'guest:admin'])->name('admin.password.email');

Route::get('/reset-password/{token}', function ($token, Request $request) {
    return view('admin.index', ['reset' => true, 'token' => $token, 'email' => $request->email]);
})->middleware(['lang', 'guest:admin'])->name('admin.password.reset');

Route::post('/reset-password', function (Request $request) {
	$request->validate([
        'token' => 'required',
        'email' => 'required|email|exists:admin_users,email',
        'password' => 'required|min:8|confirmed',
    ]);
	
	$status = Password::broker('admin_users')->reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user, $password) {
            $user->forceFill([
                'password' => $password
            ])->setRememberToken(Str::random(60));

            $user->save();
            event(new PasswordReset($user));
        }
    );
	
	if ($status === Password::PASSWORD_RESET) {
		session(['admin-reset-password' => __($status)]);
		return response()->json(['redirect' => route('admin')]);
	} else {
		return response()->json(['errors' => ['email' => __($status)]]);
	}
})->middleware(['lang', 'guest:admin'])->name('password.update');












//--------------------------------------------------------------------------------------------





// админка prefix('admin') уже подключен
Route::middleware(['lang'])->get('/{section?}', function (Request $request, $section = null) {
	if (!Auth::guard('admin')->check() && $section) return redirect()->route('admin');
	
	$activeNav = $section ?: 'common';
	
	$nav = auth('admin')->check() ?  (new AdminSection)->getSections($activeNav) : [];
	
	$hasMainAdmin = AdminUser::where('is_main_admin', true)->count() > 0;
	$locale = App::currentLocale();
	
	$user = Auth::guard('admin')->user();
	
	$settingsService = App::make(Settings::class);
	$settings = $settingsService->getMany('company_name'); // прописать настройки для вывода в общий шаблон админ. панели
	
	return view('admin.index', compact('locale', 'hasMainAdmin', 'user', 'nav', 'activeNav'), $settings->all());
})->name('admin');







// Получить данные раздела админки
Route::middleware(['lang', 'auth:admin', 'isajax:admin'])->post('/get_section', function (Request $request, Settings $settings) {
	$section = $request->input('section');
	$pageTitle = [];
	
	if (!AdminSection::where('section', $section)->count()) {
		return response()
				->view('admin.section.error', ['title' => __('custom.no_section_title'), 'message' => __('custom.no_section_message')], 200)
				->header('X-Page-Title', '');
	}
	
	if ($request->user('admin')->cannot('section-'.$section.':admin')) {
		return response()
			->view('admin.section.denied', ['title' => __('custom.denied_section_title'), 'message' => __('custom.denied_section_message')], 200)
			->header('X-Page-Title', ''/* urlencode(__('custom.denied_section_header_title')) */);
	}
	
	
	//$settingsData = $settings->getGroup($section ?: 'common') ?: [];
	
	$sectionPath = $section;
	
	$rootSection = explode('.', $sectionPath);
	if (count($rootSection) > 1) {
		$pageData = AdminSection::select('page_title')
			->where('section', $rootSection)->first();
		$pageTitle[] = $pageData['page_title'];
	}
	
	
	
	if (!View::exists('admin.section.'.$section)) {
		$sectionPath = match (true) {
			View::exists('admin.section.'.$section.'.index') => $section.'.index',
			View::exists('admin.section.'.$section.'.default') => $section.'.default',
			View::exists('admin.section.'.$section.'.'.$section) => $section.'.'.$section,
			default => false
		};
		
		if (!$sectionPath) {
			return response()
				->view('admin.section.error', ['title' => __('custom.no_section_title'), 'message' => __('custom.no_section_message')], 200)
				->header('X-Page-Title', ''/* urlencode(__('custom.no_section_header_title')) */);
		}
	} 
	
	
	$page = AdminSection::select('page_title')
		->where('section', str_replace([
			'.index','.default',$section.'.'.$section],
			['','',$section],
			$sectionPath))
		->first();
	
	$pageTitle[] = $page ? $page->page_title : null; /* urlencode(__('custom.no_section_header_title')) */
	
	return response()->view('admin.section.'.$sectionPath, []/* $settingsData */, 200)->header('X-Page-Title', json_encode($pageTitle));
});















/* Route::middleware(['lang'])->get('/', function () {
	$user = Auth::guard('admin')->user();
	$locale = App::currentLocale();
	$hasMainAdmin = AdminUser::all()->count() > 0;
	return view('admin.index', compact(['locale', 'hasMainAdmin', 'user']));
})->name('admin'); */












Route::post('/lang', function (Request $request) {
	$locale = $request->input('locale');
	if (!$locale) return response()->json(['no_locale_send' => true]);
	$locales = config('app.locales_list');
	if (!$locales) return response()->json(['no_locales' => true]);
	if (!in_array($locale, $locales)) return response()->json(['locale_not_exists' => true]);
	
	Session::put('locale', $locale);
	if (Auth::guard('admin')->check()) {
		AdminUser::where('id', $request->user('admin')->id)->update(['locale' => $locale]);
	} else {
		if (!Session::has('locale')) {
			Session::put('locale', config('app.locale'));
		}
	}
    
	App::setLocale($locale);
	return response()->json(['set_locale' => true]);
})->middleware(['isajax:admin']);





Route::post('/agreement', function (/*Request $request, Rool $rool*/) {
	//$ttt = $rool->bar();
	//$foo = $request->input('foo');
	//echo '<h1">'.$ttt.' '.$foo.'</h1>';
	
	return '<p>Настоящее Соглашение с Пользователем, регламентирует условия использования Сервиса, а
		также права и обязанности Пользователя и Администрации Сервиса.</p><p>
		Настоящее Соглашение заключается между Пользователем и Администрацией Сервиса и
		является публичной офертой в соответствии со ст. 437 Гражданско</p>';
});




































Route::post('/file', function(Request $request) {
	$path = $request->file('my_file')->store('avatars');
	return $path;
});




















// DeatH123654
// Доступ в раздел с дополнительным подтверждением пароля
/* Route::get('/confirm-password', function () {
    return view('auth.confirm-password');
})->middleware('auth:admin')->name('password.confirm');

Route::post('/confirm-password', function (Request $request) {
	if (!Hash::check($request->password, $request->user()->password)) {
        return back()->withErrors([
            'password' => ['The provided password does not match our records.']
        ]);
    }

    $request->session()->passwordConfirmed();

    return redirect()->intended();
})->middleware(['auth:admin', 'throttle:6,1']);
*/
