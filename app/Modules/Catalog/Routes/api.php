<?php

declare(strict_types=1);

use App\Modules\Catalog\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:api')->group(function (): void {

    Route::apiResource('category', CategoryController::class);

});
