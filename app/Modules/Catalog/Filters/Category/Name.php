<?php

namespace App\Modules\Catalog\Filters\Category;

use App\Modules\Catalog\Filters\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class Name implements FilterInterface
{

    /**
     * @param Builder $builder
     * @param mixed $value
     * @return Builder
     */
    public static function apply(Builder $builder, mixed $value): Builder
    {
        return $builder->where('name', 'LIKE', '%'.$value.'%');
    }
}
