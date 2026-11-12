<?php

declare(strict_types=1);

use App\Modules\Catalog\Http\Controllers\CategoryController;
use App\Modules\Catalog\Http\Controllers\CourseController;
use App\Modules\Catalog\Http\Controllers\CourseStatusController;
use App\Modules\Catalog\Http\Controllers\CurriculumController;
use App\Modules\Catalog\Http\Controllers\LessonController;
use App\Modules\Catalog\Http\Controllers\SectionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function (): void {

    Route::apiResource('category', CategoryController::class);
    Route::apiResource('course', CourseController::class);

    Route::prefix('course/{course}')->group(function (): void {

        Route::post('submit', [CourseStatusController::class, 'submit']);
        Route::post('archive', [CourseStatusController::class, 'archive']);
        Route::post('approve', [CourseStatusController::class, 'approve']);
        Route::post('reject', [CourseStatusController::class, 'reject']);
        Route::post('restore', [CourseStatusController::class, 'restore']);

        // the whole order of sections and lessons in one request, see CurriculumUpdateRequest
        Route::put('curriculum', [CurriculumController::class, 'update']);

        Route::post('section', [SectionController::class, 'store']);
        Route::put('section/{section}', [SectionController::class, 'update']);
        Route::delete('section/{section}', [SectionController::class, 'destroy']);

        Route::prefix('section/{section}')
            ->scopeBindings()
            ->controller(LessonController::class)
            ->group(function (): void {
                Route::post('lesson', 'store');
                Route::put('lesson/{lesson}', 'update');
                Route::delete('lesson/{lesson}', 'destroy');
            });

    });

});
