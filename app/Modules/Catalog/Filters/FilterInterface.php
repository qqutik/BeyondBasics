<?php

namespace App\Modules\Catalog\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

interface FilterInterface
{
    /**
     * Apply a given search value to the builder instance.
     *
     * @param Builder $builder
     * @param mixed $value
     * @return Builder $builder
     */
    public static function apply(Builder $builder, mixed $value): Builder;
}
