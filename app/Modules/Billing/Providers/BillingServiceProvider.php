<?php

declare(strict_types=1);

namespace App\Modules\Billing\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/billing.php', 'billing');
    }

    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../Routes/api.php');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
