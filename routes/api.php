<?php

use App\Http\Controllers\ApiLogController;
use App\Http\Controllers\TelegramWebhookController; // Add this line
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle']);

// Example of a protected API route (e.g., for logging or user-related API)
Route::middleware('auth:api')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/api-logs', [ApiLogController::class, 'index']);
});