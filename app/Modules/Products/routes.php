<?php

use App\Modules\Products\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('products')->group(function () {
    // Public reads
    Route::get('/', [ProductController::class, 'index']);
    Route::get('{product}', [ProductController::class, 'show']);

    // Admin writes
    Route::middleware(['auth:api', 'role:admin'])->group(function () {
        Route::post('/', [ProductController::class, 'store']);
        Route::put('{product}', [ProductController::class, 'update']);
        Route::patch('{product}/status', [ProductController::class, 'changeStatus']);
    });
});
