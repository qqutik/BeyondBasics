<?php

declare(strict_types=1);

use App\Modules\Catalog\Providers\CatalogServiceProvider;
use App\Modules\Identity\Providers\IdentityServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    IdentityServiceProvider::class,
    CatalogServiceProvider::class,
];
