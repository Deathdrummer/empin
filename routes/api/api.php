<?php

use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\MessengerApiController;
use App\Http\Controllers\Api\MessengerCallController;
use App\Http\Controllers\api\TimesheetApiController;
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




// Авторизация (без защиты)
Route::controller(AuthController::class)->prefix('auth')->group(function() {
	Route::post('/login', 'login');
	Route::post('/logout', 'logout')->middleware('auth:sanctum');
	Route::get('/me', 'me')->middleware('auth:sanctum');
});

// Timesheet API (требует авторизацию)
Route::controller(TimesheetApiController::class)->prefix('timesheet')->middleware('auth:sanctum')->group(function() {
	Route::post('/slides', 'getSlidesData');
	Route::post('/slide', 'getSlideData');
	Route::get('/staff', 'getStaff');
	Route::get('/all-staff', 'getAllStaff');
	Route::get('/filter-options', 'getFilterOptions');
	Route::get('/filter-options/teams/search', 'searchTeams');
	Route::get('/filter-options/contracts/search', 'searchContracts');
	Route::get('/filter-options/contracts/search-all', 'searchAllContracts');
	Route::get('/contracts/search', 'contractsList');
	Route::post('/team', 'addTeam');
	Route::delete('/team/{id}', 'removeTeam');
	Route::post('/contract', 'addContract');
	Route::delete('/contract/{id}', 'removeContract');
	Route::post('/comment', 'addComment');
	Route::post('/comment/reaction', 'toggleReaction');
	Route::put('/comment/{id}', 'updateComment')->where('id', '[0-9]+');
	Route::delete('/comment/{id}', 'removeComment')->where('id', '[0-9]+');
});

// Messenger API (требует авторизацию)
Route::controller(MessengerApiController::class)->prefix('messenger')->middleware('auth:sanctum')->group(function() {
	Route::post('/chat', 'getOrCreateChat');
	Route::post('/chat/messages', 'getMessages');
	Route::post('/message', 'addMessage');
	Route::put('/message/{id}', 'updateMessage');
	Route::delete('/message/{id}', 'removeMessage');
	Route::post('/message/reaction', 'toggleReaction');
});

// Messenger Calls API (требует авторизацию)
Route::prefix('messenger/calls')->middleware('auth:sanctum')->group(function() {
	Route::post('/initiate', [MessengerCallController::class, 'initiate']);
	Route::post('/{id}/accept', [MessengerCallController::class, 'accept']);
	Route::post('/{id}/reject', [MessengerCallController::class, 'reject']);
	Route::post('/{id}/cancel', [MessengerCallController::class, 'cancel']);
	Route::post('/{id}/end', [MessengerCallController::class, 'end']);
	Route::get('/history', [MessengerCallController::class, 'history']);
	Route::get('/{id}', [MessengerCallController::class, 'show']);
});

// Settings API
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

