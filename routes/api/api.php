<?php

use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/* Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
 */




// регистрация, авторизация, выход
Route::controller(SettingsController::class)/* ->middleware(['lang', 'auth:admin', 'isajax:admin']) */->group(function() {
	Route::post('/settings', 'get');
	Route::put('/settings', 'set');
	Route::delete('/settings', 'remove');
});



/* Route::post('/setting', function (Request $request, Settings $settings) {
	
	$set = $request->input('setting');
	$value = $request->input('value');
	
	$d = explode(':', $set);
	
	$group = $d[0];
	$setting = $d[1];
	
	if (!$group || !$setting) return false;
	
	
	
	$s = explode('.', $setting);
	if (count($s) > 1) {
		$settings->setJson($group, array_shift($s), implode('.', $s), $value);
	} else {
		$settings->set($group, $s[0], $value);
	}
	
	
	//echo $rool->bar();
	//if (Gate::check('test')) return view('admin.auth.auth');
    //return view('admin.index');
})->middleware(['lang', 'auth:admin', 'isajax:admin']); */



/* Route::delete('/setting', function (Request $request, Settings $settings) {
	
}
 */

