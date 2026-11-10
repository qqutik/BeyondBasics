<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One filter = one field. Reads its typed value from the filter DTO, never from the request.
 *
 * @template TModel of Model
 * @template TFilter of object
 */
interface QueryFilterInterface
{
    /**
     * @param  Builder<TModel>  $query
     * @param  TFilter  $filter
     */
    public function apply(Builder $query, object $filter): void;
}
