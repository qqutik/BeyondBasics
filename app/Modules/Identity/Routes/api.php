<?php

declare(strict_types=1);

use App\Modules\Identity\Http\Controllers\AuthController;
use App\Modules\Identity\Http\Controllers\EmailVerificationController;
use App\Modules\Identity\Http\Controllers\PasswordResetController;
use App\Modules\Identity\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Loaded by IdentityServiceProvider with the "api" middleware group and the /api prefix.
// Stateless: authenticate with "Authorization: Bearer <access_token>" (guard "api", driver jwt).

Route::prefix('auth')->name('identity.auth.')->group(function (): void {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1')->name('register');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('throttle:30,1')->name('refresh');

    Route::post('forgot-password', [PasswordResetController::class, 'forgot'])->middleware('throttle:6,1')->name('password.forgot');
    Route::post('reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:6,1')->name('password.reset');

    Route::middleware('auth:api')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('email/resend', [EmailVerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.resend');
    });
});

// Name "verification.verify" is what Laravel's VerifyEmail notification links to.
Route::get('auth/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->whereNumber('id')
    ->name('verification.verify');

Route::middleware('auth:api')->get('me', [UserController::class, 'me'])->name('identity.me');
