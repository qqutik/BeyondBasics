<?php

declare(strict_types=1);

use App\Modules\Billing\Http\Controllers\OrderController;

Route::middleware('auth:api')->group(function (): void {

    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{uuid}', [OrderController::class, 'show'])->whereUuid('uuid');

});
