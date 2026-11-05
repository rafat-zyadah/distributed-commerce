<?php

use App\Modules\Orders\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('orders')->middleware('auth:api')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::get('{order}', [OrderController::class, 'show']);

    Route::post('/', [OrderController::class, 'store'])->middleware('role:customer');
    Route::patch('{order}/status', [OrderController::class, 'updateStatus'])->middleware('role:admin');
});
