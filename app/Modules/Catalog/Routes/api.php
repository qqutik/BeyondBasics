<?php

declare(strict_types=1);

use App\Modules\Catalog\Http\Controllers\CategoryController;
use App\Modules\Catalog\Http\Controllers\CourseController;
use App\Modules\Catalog\Http\Controllers\CourseStatusController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:api')->group(function (): void {

    Route::apiResource('category', CategoryController::class);
    Route::apiResource('course', CourseController::class);

    Route::prefix('course/{course}')
        ->controller(CourseStatusController::class)
        ->group(function (): void {

            Route::post('submit', 'submit');
            Route::post('archive', 'archive');

            Route::post('approve', 'approve');
            Route::post('reject', 'reject');
            Route::post('restore', 'restore');
        });

});
