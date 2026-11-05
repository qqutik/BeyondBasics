<?php

declare(strict_types=1);

namespace App\Modules\Identity\Providers;

use App\Modules\Identity\Listeners\RecordLastLoginListener;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__ . '/../Routes/api.php');

        Event::listen(Login::class, RecordLastLoginListener::class);

        // 5 attempts per minute per email + IP.
        RateLimiter::for('login', static function (Request $request): Limit {
            $email = $request->input('email');

            return Limit::perMinute(5)->by(Str::lower(is_string($email) ? $email : '').'|'.$request->ip());
        });

        // The API has no reset page: the link in the email points to the frontend,
        // which then calls POST /api/auth/reset-password with the token.
        ResetPassword::createUrlUsing(static function (mixed $user, string $token): string {
            $frontend = config('identity.frontend_url');

            return rtrim(is_string($frontend) ? $frontend : '', '/').'/reset-password?'.http_build_query([
                'token' => $token,
                'email' => $user instanceof CanResetPassword ? $user->getEmailForPasswordReset() : '',
            ]);
        });
    }
}
