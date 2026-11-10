<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Traits;

use Illuminate\Support\Str;

trait SlugTrait
{
    public function generateSlug(string $title): string
    {
        return Str::slug($title);
    }
}
