<?php

use App\Http\Controllers\site\Contracts;
use App\Http\Controllers\site\Selections;
use App\Http\Controllers\site\Timesheet;
use App\Http\Controllers\UserController;
use App\Http\Requests\Auth\UserEmailVerificationRequest;
use App\Models\Department as DepartmentModel;
use App\Models\Section;
use App\Models\User;
use App\Services\Business\User as BusinessUser;
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
Route::controller(UserController::class)->middleware(['lang', 'isajax:site'])->group(function() {
	//Route::get('/reg', 'regForm')->name('site.reg');
	//Route::post('/register', 'register');
	Route::get('/auth', 'authForm')->name('site.auth');
	Route::post('/login', 'login');
	Route::get('/logout', 'logout')->name('site.logout');
});



// подтверждение адреса почты
/* Route::get('/email/verify', function () {
    return view('site.auth.verify-email');
})->middleware('auth:site')->name('site.verification.notice'); */

Route::get('/email/verify/{id}/{hash}', function (UserEmailVerificationRequest $request) {
	$request->fulfill();
	session(['site-email-verified' => __('auth.email_verified')]);
	return redirect(route('site'));
})->middleware(['lang', 'auth:site', 'signed'])->name('site.verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification('site');
    return response()->json(['sending' => __('auth.email_verify_sending')]);
})->middleware(['lang', 'auth:site', 'throttle:6,1'])->name('site.verification.send');




// Сброс пароля
Route::get('/forgot-password', function (Request $request) {
	$email = encodeEmail($request->input('email'));
	return view('site.auth.forgot-password', ['email' => $email]);
})->middleware('lang', 'guest:site')->name('password.request');

Route::post('/forgot-password', function (Request $request) {
	$request->merge(['email' => encodeEmail($request->input('email'))]);
	$request->validate(['email' => 'required|email|exists:users,email']);
    $status = Password::broker('users')->sendResetLink($request->only('email'), function($user, $token) {
		$user->sendPasswordResetNotification($token, 'site');
	});
	
	if ($status === Password::RESET_LINK_SENT) {
		return response()->json(['message' => __($status)]);
	} else {
		return response()->json(['errors' => ['email' => [__($status)]]]);
	}
})->middleware(['lang', 'guest:site'])->name('site.password.email');

Route::get('/reset-password/{token}', function ($token, Request $request) {
    return view('site.index', ['reset' => true, 'token' => $token, 'email' => encodeEmail($request->email)]);
})->middleware(['lang', 'guest:site'])->name('site.password.reset');

Route::post('/reset-password', function (Request $request) {
	$request->validate([
        'token' => 'required',
        'email' => 'required|email|exists:users,email',
        'password' => 'required|min:8|confirmed',
    ]);
	
	$status = Password::broker('users')->reset(
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
		session(['site-reset-password' => __($status)]);
		return response()->json(['redirect' => route('site')]);
	} else {
		return response()->json(['errors' => ['email' => __($status)]]);
	}
})->middleware(['lang', 'guest:site'])->name('password.update');









// тест перечисления enum Period
/* Route::get('test', function() {
	$status = Period::tryFrom(request('period'));
	
	if ($status) dd($status->date()->locale('ru')->isoFormat('DD MMMM YYYY', 'Do MMMM'));
	else echo 'no';
}); */








//--------------------------------------------------------------------------------------------





// сайт prefix('site') уже подключен
Route::middleware(['lang'])->get('/{section?}', function (Request $request, $section = null) {
	if (!Auth::guard('site')->check() && $section) return redirect()->route('site');
	
	$settingsService = App::make(Settings::class);
	$settings = $settingsService->getMany('company_name', 'site_start_page', 'show_nav'); // прописать настройки для вывода в общий шаблон личного кабинета
	
	$activeNav = $section ?: ($settings['site_start_page'] ?? 'common');
	
	$locale = App::currentLocale();
	
	$nav = auth('site')->check() ? (new Section)->getSections($activeNav) : [];
	
	$user = User::with('userinfo')->find(Auth::guard('site')->id());
	
	return view('site.index', compact('locale', 'user', 'nav', 'activeNav'), $settings->all());
})->name('site');






// Получить данные раздела
Route::middleware(['lang', 'auth:site', 'isajax:site'])->post('/get_section', function (Request $request, Settings $settings) {
	$section = $request->input('section');
	$pageTitle = [];
	
	if (!Section::where('section', $section)->count()) {
		return response()
				->view('site.section.error', ['title' => __('custom.no_section_title'), 'message' => __('custom.no_section_message')], 200)
				->header('X-Page-Title', '');
	}
	
	if ($request->user('site')->cannot('section-'.$section.':site')) {
		return response()
			->view('site.section.denied', ['title' => __('custom.denied_section_title'), 'message' => __('custom.denied_section_message')], 200)
			->header('X-Page-Title', ''/* urlencode(__('custom.denied_section_header_title')) */);
	}
	
	
	$sectionPath = $section;
	
	$rootSection = explode('.', $sectionPath);
	if (count($rootSection) > 1) {
		$pageData = Section::select('page_title')
			->where('section', $rootSection)->first();
		$pageTitle[] = $pageData['page_title'];
	}
	
	if (!View::exists('site.section.'.$section)) {
		$sectionPath = match (true) {
			View::exists('site.section.'.$section.'.index') => $section.'.index',
			View::exists('site.section.'.$section.'.default') => $section.'.default',
			View::exists('site.section.'.$section.'.'.$section) => $section.'.'.$section,
			default => false
		};
		
		if (!$sectionPath) {
			return response()
				->view('site.section.error', ['title' => __('custom.no_section_title'), 'message' => __('custom.no_section_message')], 200)
				->header('X-Page-Title', ''/* urlencode(__('custom.no_section_header_title')) */);
		}
	} 
	
	
	$page = Section::select('page_title', 'settings')
		->where('section', str_replace([
			'.index','.default',$section.'.'.$section],
			['','',$section],
			$sectionPath))
		->first();
	
	// в таблице sections прописывается массив тех настроек, что нужно подгрузить
	$settingsData = $page['settings'] ? ($settings->getMany($page['settings'])->toArray() ?: []) : []; 
	
	
	$pageTitle[] = $page ? $page->page_title : null; /* urlencode(__('custom.no_section_header_title')) */
	
	$user = Auth::guard('site')->user();
	
	$departments = DepartmentModel::select(['id', 'name'])->orderBy('_sort', 'ASC')->get();
	
	//$data = array_merge(, ['user' => $user, 'departments' => $departments]);
	
	$data = [
		'user' 			=> $user,
		'departments' 	=> $departments,
		'setting' 		=> $settingsData
	];
	
	
	return response()->view('site.section.'.$sectionPath, $data/* сюда данные */, 200)->header('X-Page-Title', json_encode($pageTitle));
});

















Route::post('/lang', function (Request $request) {
	$locale = $request->input('locale');
	if (!$locale) return response()->json(['no_locale_send' => true]);
	$locales = config('app.locales_list');
	if (!$locales) return response()->json(['no_locales' => true]);
	if (!in_array($locale, $locales)) return response()->json(['locale_not_exists' => true]);
	
	Session::put('locale', $locale);
	if (Auth::guard('site')->check()) {
		User::where('id', $request->user('site')->id)->update(['locale' => $locale]);
	} else {
		if (!Session::has('locale')) {
			Session::put('locale', config('app.locale'));
		}
	}
    
	App::setLocale($locale);
	return response()->json(['set_locale' => true]);
})->middleware(['isajax:site']);










//---------------------------------------------------------------------------------


Route::prefix('site')->middleware(['lang', 'isajax:site'])->group(function() {
	// Договоры
	
	Route::get('/contracts', [Contracts::class, 'list']);
	Route::get('/contracts/counts', [Contracts::class, 'counts']);
	Route::put('/contracts', [Contracts::class, 'set_data']);
	Route::post('/contracts/hide', [Contracts::class, 'hide']);
	Route::post('/contracts/to_archive', [Contracts::class, 'to_archive']);
	Route::post('/contracts/to_work', [Contracts::class, 'to_work']);
	Route::get('/contracts/departments', [Contracts::class, 'departments']);
	
	Route::get('/contracts/statuses', [Contracts::class, 'statuses']);
	Route::put('/contracts/set_status', [Contracts::class, 'set_status']);
	
	Route::get('/contracts/column_values', [Contracts::class, 'column_values']);
	
	Route::get('/contracts/calendar', [Contracts::class, 'calendar']);
	
	Route::get('/contracts/work_calendar_count', [Contracts::class, 'work_calendar_count']);
	
	
	
	Route::get('/contracts/colums', [Contracts::class, 'colums']);
	Route::put('/contracts/colums', [Contracts::class, 'set_colums']);
	
	Route::get('/contracts/sortdeps', [Contracts::class, 'sortdeps']);
	Route::put('/contracts/sortdeps', [Contracts::class, 'set_sortdeps']);
	
	
	Route::post('/contracts/send', [Contracts::class, 'send']);
	
	Route::put('/contracts/check_new', [Contracts::class, 'check_new']);
	Route::put('/contracts/pin', [Contracts::class, 'pin']);
	
	Route::get('/contracts/colorselections', [Contracts::class, 'colorselections']);
	Route::post('/contracts/colorselections', [Contracts::class, 'set_colorselection']);
	
	
	
	Route::get('/contracts/common_info', [Contracts::class, 'get_common_info']);
	Route::put('/contracts/common_info', [Contracts::class, 'set_common_info']);
	Route::delete('/contracts/common_info', [Contracts::class, 'clear_common_info']);
	
	Route::get('/contracts/chat', [Contracts::class, 'chat_get']);
	Route::put('/contracts/chat', [Contracts::class, 'chat_send']);
	Route::put('/contracts/chats', [Contracts::class, 'chat_send_many']);
	
	Route::post('/contracts/step_checkbox', [Contracts::class, 'step_checkbox']);
	
	Route::get('/contracts/settings', [Contracts::class, 'settings']);
	Route::post('/contracts/settings', [Contracts::class, 'set_setting']);
	
	Route::get('/contracts/cell_comment', [Contracts::class, 'cell_comment']);
	Route::post('/contracts/cell_comment', [Contracts::class, 'set_cell_comment']);
	
	Route::get('/contracts/cell_lights', [Contracts::class, 'cell_lights']);
	
	Route::get('/contracts/cell_edit', [Contracts::class, 'cell_edit']);
	Route::post('/contracts/cell_edit', [Contracts::class, 'set_cell_edit']);
	
	Route::get('/contracts/to_export', [Contracts::class, 'get_to_export']);
	Route::post('/contracts/to_export', [Contracts::class, 'set_to_export']);
	
	Route::get('/contracts/edit_acts', [Contracts::class, 'get_edit_acts_form']);
	Route::post('/contracts/edit_acts', [Contracts::class, 'set_edit_acts']);
	
	Route::get('/contracts/export_act', [Contracts::class, 'export_act_form']);
	Route::post('/contracts/export_act', [Contracts::class, 'export_act']);
	Route::post('/contracts/export_act_ranged', [Contracts::class, 'export_act_ranged']);
	Route::post('/contracts/export_act_template', [Contracts::class, 'export_act_template']);
	Route::put('/contracts/export_increment_count', [Contracts::class, 'export_increment_count']);
	
	Route::get('/contracts/contract_selections', [Contracts::class, 'contract_selections']);
	Route::get('/contracts/selections_to_choose', [Contracts::class, 'selections_to_choose']);
	
	
	
	
	// Подборки
	Route::get('/selections/init', [Selections::class, 'init']);
	Route::put('/selections/add_contract', [Selections::class, 'add_contract']);
	Route::put('/selections/add_contracts', [Selections::class, 'add_contracts']);
	Route::put('/selections/remove_contract', [Selections::class, 'remove_contract']);
	Route::put('/selections/remove_contracts', [Selections::class, 'remove_contracts']);
	Route::post('/selections/store_show', [Selections::class, 'store_show']);
	
	Route::put('/selections/sort', [Selections::class, 'sort']);
	
	Route::get('/selections/users_to_share', [Selections::class, 'users_to_share']);
	Route::post('/selections/share', [Selections::class, 'share']);
	Route::post('/selections/unsubscribe', [Selections::class, 'unsubscribe']);
	
	Route::put('/selections/archive', [Selections::class, 'archive']);
	
	Route::post('/selections/add_selection_from_contextmenu', [Selections::class, 'add_selection_from_contextmenu']);
	
	Route::resource('selections', Selections::class);
	
	
	
	
	
	
	
	
	// План-график работ
	Route::get('/timesheet/init', [Timesheet::class, 'init']);
	Route::get('/timesheet/slides', [Timesheet::class, 'getSlidesData']);
	Route::get('/timesheet/contracts', [Timesheet::class, 'contractsList']);
	
	Route::post('/timesheet/team', [Timesheet::class, 'addTeam']);
	Route::post('/timesheet/contract', [Timesheet::class, 'addContract']);
	Route::post('/timesheet/comment', [Timesheet::class, 'addComment']);
	
	Route::delete('/timesheet/team', [Timesheet::class, 'removeTeam']);
	Route::delete('/timesheet/contract', [Timesheet::class, 'removeContract']);
	Route::delete('/timesheet/comment', [Timesheet::class, 'removeComment']);
	
	Route::get('/timesheet/staff', [Timesheet::class, 'getStaff']);
	
	
	
	
});







Route::fallback(function () {
    return;
});
























// Route::post('/agreement', function (/*Request $request, Rool $rool*/) {
// 	//$ttt = $rool->bar();
// 	//$foo = $request->input('foo');
// 	//echo '<h1">'.$ttt.' '.$foo.'</h1>';
// 	
// 	return '<p>Настоящее Соглашение с Пользователем, регламентирует условия использования Сервиса, а
// 		также права и обязанности Пользователя и Администрации Сервиса.</p><p>
// 		Настоящее Соглашение заключается между Пользователем и Администрацией Сервиса и
// 		является публичной офертой в соответствии со ст. 437 Гражданско</p>';
// });


