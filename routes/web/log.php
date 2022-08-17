<?php

use App\Http\Controllers\LogController;
use Illuminate\Support\Facades\Route;


// Логи
//Route::controller(LogController::class)->prefix('logs')->name('log.')/*->middleware('auth:admin')*/->group(function() {
//	Route::get('/', 'index')->name('view');
//	Route::get('/clear', 'clear')->name('clear');
//});