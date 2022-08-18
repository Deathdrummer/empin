<?php namespace App\Http\Controllers;

use App\Http\Requests\Auth\AdminRegRequest;
use App\Models\AdminUser;
use App\Services\Settings;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

class AdminController extends Controller {
    
	/**
	 * @param 
	 * @return  
	 */
	public function authForm() {
		if (Auth::guard('admin')->check()) return response()->json(['auth' => true]);
		session(['admin-auth-view' => 'admin.auth.auth']);
		$locale = App::currentLocale();
		$hasMainAdmin = AdminUser::where('is_main_admin', 1)->count() > 0;
		
		$settingsService = App::make(Settings::class);
		$settings = $settingsService->getMany('company_name');
		
		return view('admin.auth.auth', compact('locale', 'hasMainAdmin'), $settings->all());
	}
	
	/**
	 * @param 
	 * @return 
	 */
	public function login(Request $request) {
		$authFields = $request->validate([
			'email' 	=> 'required|email|exists:admin_users,email',
			'password' 	=> 'required|string'
		]);
		if (!Auth::guard('admin')->attempt($authFields, true)) return response()->json(['no_auth' => __('auth.failed')]);
		
		if (!Auth::guard('admin')->user()->is_main_admin && !Auth::guard('admin')->user()->email_verified_at) {
			AdminUser::where('id', Auth::guard('admin')->user()->id)->update(['email_verified_at' => Date::now()]);
		}
		
		$redirect = $request->session()->pull('auth_redirect', '/admin');
		$request->session()->regenerate();
		session(['admin-login' => __('auth.auth_success')]);
		return response()->json(['redirect' => $redirect]);
	}
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function regForm(Request $request) {
		//if (Auth::guard('admin')->check()) return redirect('/admin');
		session(['admin-auth-view' => 'admin.auth.reg']);
		$locale = App::currentLocale();
		
		$settingsService = App::make(Settings::class);
		$settings = $settingsService->getMany('company_name');
		
		return view('admin.auth.reg', compact('locale'), $settings->all());
	}
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function register(AdminRegRequest $req) {
		$validFields = $req->validated();
		if (AdminUser::where('is_main_admin', true)->count() == 0) $validFields['is_main_admin'] = true;
		if (!$user = AdminUser::create($validFields)) return response()->json(['reg' => __('auth.reg_failed')]);
		event(new Registered($user));
		Auth::guard('admin')->login($user, true);
		session(['admin-register' => __('auth.reg_success')]);
		session()->forget('admin-auth-view');
		return response()->json(['reg' => __('auth.reg_success')]);
	}
	
	
	
	
	public function logout(Request $request) {
		if (!Auth::guard('admin')->check()) return response()->json(['no_auth' => true]);
	    $locale = $request->session()->pull('locale');
	    Auth::guard('admin')->logout();
		//Auth::logoutOtherDevices($request->getPassword());
	    $request->session()->invalidate();
	    $request->session()->regenerateToken();
	    $request->session()->put('locale', $locale);
		return response()->json(['logout' => true]);
	}
}