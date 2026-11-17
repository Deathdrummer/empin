<?php

use App\Http\Controllers\api\ProductionController;
use Illuminate\Support\Facades\Route;

/*
| Производственный модуль (ТЗ ЭМПИН-pro). Отдельный файл маршрутов, чтобы не смешиваться с прототипом табеля.
*/
Route::prefix('production')->middleware(['auth:sanctum', 'presence'])->group(function () {
    Route::get('/time', [ProductionController::class, 'time']);
    Route::get('/me', [ProductionController::class, 'me']);
});
