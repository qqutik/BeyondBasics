<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Filters\Course;

use App\Modules\Catalog\DTO\Category\CategoryFilterDTO;
use App\Modules\Catalog\DTO\Course\CourseFilterDTO;
use App\Modules\Catalog\Filters\QueryFilterInterface;
use App\Modules\Catalog\Models\Category;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;

final readonly class CourseFilters
{
    /**
     * @var list<class-string<QueryFilterInterface<Category, CategoryFilterDTO>>>
     */
    private const array FILTERS = [
        TitleFilter::class,
    ];

    public function __construct(
        private Container $container,
    ) {}

    /**
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    public function apply(Builder $query, CourseFilterDTO $filter): Builder
    {
        foreach (self::FILTERS as $class) {
            $this->container->make($class)->apply($query, $filter);
        }

        return $query;
    }
}
