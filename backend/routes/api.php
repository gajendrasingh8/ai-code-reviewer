<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CodeReviewController;

Route::prefix('v1')->group(function () {
    // Code Review routes
    Route::post('/reviews', [CodeReviewController::class, 'store']);
    Route::get('/stats',   [CodeReviewController::class, 'stats']);
});
