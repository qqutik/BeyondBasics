<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Filters\Category;

use App\Modules\Catalog\DTO\Category\CategoryFilterDTO;
use App\Modules\Catalog\Filters\QueryFilterInterface;
use App\Modules\Catalog\Models\Category;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;

final readonly class CategoryFilters
{
    /**
     * @var list<class-string<QueryFilterInterface<Category, CategoryFilterDTO>>>
     */
    private const array FILTERS = [
        NameFilter::class,
        ParentIdFilter::class,
        IsActiveFilter::class,
    ];

    public function __construct(
        private Container $container,
    ) {}

    /**
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    public function apply(Builder $query, CategoryFilterDTO $filter): Builder
    {
        foreach (self::FILTERS as $class) {
            $this->container->make($class)->apply($query, $filter);
        }

        return $query;
    }
}
