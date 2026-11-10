<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Helpers;

final class LikeHelper
{
    /**
     * Pattern for "contains", with user input treated literally (% and _ are not wildcards).
     */
    public static function contains(string $value): string
    {
        return '%' . addcslashes($value, '\\%_') . '%';
    }

    public static function startsWith(string $value): string
    {
        return addcslashes($value, '\\%_') . '%';
    }
}
