<?php

declare(strict_types=1);

use App\Modules\Catalog\Http\Controllers\CatalogController;
use App\Modules\Catalog\Http\Controllers\CategoryController;
use App\Modules\Catalog\Http\Controllers\CourseController;
use App\Modules\Catalog\Http\Controllers\CourseStatusController;
use App\Modules\Catalog\Http\Controllers\CurriculumController;
use App\Modules\Catalog\Http\Controllers\LessonController;
use App\Modules\Catalog\Http\Controllers\SectionController;
use Illuminate\Support\Facades\Route;

// Public catalog for students: no login, rate limited, only published courses.
Route::middleware('throttle:120,1')->group(function (): void {
    Route::get('courses', [CatalogController::class, 'index']);
    Route::get('courses/{slug}', [CatalogController::class, 'show'])->where('slug', '[a-z0-9-]+');
});

Route::middleware('auth:api')->group(function (): void {

    Route::apiResource('category', CategoryController::class);
    Route::apiResource('course', CourseController::class);

    // scopeBindings: {section} must belong to {course} and {lesson} to {section}, otherwise 404.
    // Without it PUT course/{own}/section/{foreign} passes the policy on the own course and edits a foreign section.
    Route::prefix('course/{course}')->scopeBindings()->group(function (): void {

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
