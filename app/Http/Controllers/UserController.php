<?php namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

class UserController extends Controller {
    
	/**
	 * @param 
	 * @return  
	 */
	public function authForm() {
		if (Auth::guard('site')->check()) return response()->json(['auth' => true]);
		session(['site-auth-view' => 'site.auth.auth']);
		$locale = App::currentLocale();
		return view('site.auth.auth', compact(['locale']));
	}
	
	/**
	 * @param 
	 * @return 
	 */
	public function login(Request $request) {
		$authFields = $request->validate([
			'email' 	=> 'required|email|exists:users,email',
			'password' 	=> 'required|string'
		]);
		if (!Auth::guard('site')->attempt($authFields, true)) return response()->json(['no_auth' => __('auth.failed')]);
		
		if (!Auth::guard('site')->user()->email_verified_at) {
			User::where('id', Auth::guard('site')->user()->id)->update(['email_verified_at' => Date::now()]);
		}
		
		$redirect = $request->session()->pull('auth_redirect', '/');
		$request->session()->regenerate();
		session(['site-login' => __('auth.auth_success')]);
		return response()->json(['redirect' => $redirect]);
	}
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	//public function regForm(Request $request) {
	//	session(['site-auth-view' => 'site.auth.reg']);
	//	$locale = App::currentLocale();
	//	return view('site.auth.reg', compact(['locale']));
	//}
	
	
	/**
	 * @param 
	 * @return 
	 */
	//public function register(RegRequest $req) {
	//	$validFields = $req->validated();
	//	if (!$user = User::create($validFields)) return response()->json(['reg' => __('auth.reg_failed')]);
	//	event(new Registered($user));
	//	Auth::guard('site')->login($user, true);
	//	session(['site-register' => __('auth.reg_success')]);
	//	session()->forget('site-auth-view');
	//	return response()->json(['reg' => __('auth.reg_success')]);
	//}
	
	
	
	
	public function logout(Request $request) {
		if (!Auth::guard('site')->check()) return response()->json(['no_auth' => true]);
	    $locale = $request->session()->pull('locale');
	    Auth::guard('site')->logout();
		//Auth::logoutOtherDevices($request->getPassword());
	    $request->session()->invalidate();
	    $request->session()->regenerateToken();
	    $request->session()->put('locale', $locale);
		return response()->json(['logout' => true]);
	}
}