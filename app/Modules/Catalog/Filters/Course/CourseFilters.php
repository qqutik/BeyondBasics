<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Filters\Course;

use App\Modules\Catalog\DTO\Course\CourseFilterDTO;
use App\Modules\Catalog\Filters\QueryFilterInterface;
use App\Modules\Catalog\Models\Course;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;

final readonly class CourseFilters
{
    /**
     * @var list<class-string<QueryFilterInterface<Course, CourseFilterDTO>>>
     */
    private const array FILTERS = [
        UserIdFilter::class,
        TitleFilter::class,
    ];

    public function __construct(
        private Container $container,
    ) {}

    /**
     * @param  Builder<Course>  $query
     * @return Builder<Course>
     */
    public function apply(Builder $query, CourseFilterDTO $filter): Builder
    {
        foreach (self::FILTERS as $class) {
            $this->container->make($class)->apply($query, $filter);
        }

        return $query;
    }
}
