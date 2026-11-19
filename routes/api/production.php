<?php

use App\Http\Controllers\api\ProductionAttendanceController;
use App\Http\Controllers\api\ProductionController;
use App\Http\Controllers\api\ProductionDirectoryController;
use App\Http\Controllers\api\ProductionTaskChatController;
use App\Http\Controllers\api\ProductionTasksController;
use Illuminate\Support\Facades\Route;

/* Производственный модуль (ТЗ ЭМПИН-pro) */
Route::prefix('production')->middleware(['auth:sanctum', 'presence'])->group(function () {
    Route::get('/time', [ProductionController::class, 'time']);
    Route::get('/me', [ProductionController::class, 'me']);

    // Данные ERP для формы задания (раздел 5)
    Route::get('/objects', [ProductionDirectoryController::class, 'objects']);
    Route::get('/objects/{objectId}/plan', [ProductionDirectoryController::class, 'plan'])->whereNumber('objectId');
    Route::get('/executors', [ProductionDirectoryController::class, 'executors']);
    Route::get('/vehicles', [ProductionDirectoryController::class, 'vehicles']);
    Route::get('/work-types', [ProductionDirectoryController::class, 'workTypes']);

    // Задания и календарь (раздел 6)
    Route::get('/tasks', [ProductionTasksController::class, 'index']);
    Route::get('/tasks/dates', [ProductionTasksController::class, 'dates']);
    Route::get('/tasks/filters', [ProductionTasksController::class, 'filters']);
    Route::post('/tasks', [ProductionTasksController::class, 'store']);
    Route::get('/tasks/{id}', [ProductionTasksController::class, 'show'])->whereNumber('id');
    Route::patch('/tasks/{id}', [ProductionTasksController::class, 'update'])->whereNumber('id');
    Route::delete('/tasks/{id}', [ProductionTasksController::class, 'destroy'])->whereNumber('id');
    Route::post('/tasks/{id}/copy', [ProductionTasksController::class, 'copy'])->whereNumber('id');
    Route::post('/tasks/{id}/invites', [ProductionTasksController::class, 'invite'])->whereNumber('id');

    // Переписка задания (П-ЗД-20…24, ИС-ЗД-16)
    Route::get('/tasks/{id}/comments', [ProductionTaskChatController::class, 'index'])->whereNumber('id');
    Route::post('/tasks/{id}/comments', [ProductionTaskChatController::class, 'store'])->whereNumber('id');
    Route::put('/tasks/{id}/comments/{commentId}', [ProductionTaskChatController::class, 'update'])->whereNumber(['id', 'commentId']);
    Route::delete('/tasks/{id}/comments/{commentId}', [ProductionTaskChatController::class, 'destroy'])->whereNumber(['id', 'commentId']);
    Route::post('/tasks/{id}/comments/{commentId}/reaction', [ProductionTaskChatController::class, 'reaction'])->whereNumber(['id', 'commentId']);
    Route::delete('/tasks/{id}/invites/{staffId}', [ProductionTasksController::class, 'revokeInvite'])->whereNumber(['id', 'staffId']);

    // Выход на смену (раздел 7): плашка, самоотметка, заявка состава, подтверждение в задании
    Route::get('/attendance/today', [ProductionAttendanceController::class, 'today']);
    Route::post('/attendance/mark', [ProductionAttendanceController::class, 'mark']);
    Route::post('/attendance/crew', [ProductionAttendanceController::class, 'crew']);
    Route::post('/tasks/{id}/attendance', [ProductionAttendanceController::class, 'confirm'])->whereNumber('id');

    // Уведомления до ручного закрытия (п. 9.3)
    Route::get('/notifications', [ProductionController::class, 'notifications']);
    Route::post('/notifications/{id}/close', [ProductionController::class, 'closeNotification'])->whereNumber('id');
});
