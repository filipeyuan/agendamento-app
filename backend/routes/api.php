<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AssistantController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BusinessHourController;
use App\Http\Controllers\Api\ScheduleBlockController;
use App\Http\Controllers\Api\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{service}/available-slots', [AppointmentController::class, 'availableSlots']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::get('/appointments/mine', [AppointmentController::class, 'mine']);

    Route::post('/assistant/chat', [AssistantController::class, 'chat']);

    Route::prefix('admin')->group(function () {
        Route::get('/services', [ServiceController::class, 'adminIndex']);
        Route::post('/services', [ServiceController::class, 'store']);
        Route::put('/services/{service}', [ServiceController::class, 'update']);
        Route::delete('/services/{service}', [ServiceController::class, 'destroy']);

        Route::get('/appointments', [AppointmentController::class, 'adminIndex']);
        Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);

        Route::get('/business-hours', [BusinessHourController::class, 'index']);
        Route::put('/business-hours', [BusinessHourController::class, 'update']);

        Route::get('/schedule-blocks', [ScheduleBlockController::class, 'index']);
        Route::post('/schedule-blocks', [ScheduleBlockController::class, 'store']);
        Route::delete('/schedule-blocks/{scheduleBlock}', [ScheduleBlockController::class, 'destroy']);
    });
});
