<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/catalog.php', 'catalog');
    }

    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../Routes/api.php');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
