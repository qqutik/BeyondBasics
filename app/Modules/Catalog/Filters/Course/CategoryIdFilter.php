<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Filters\Course;

use App\Modules\Catalog\DTO\Course\CourseFilterDTO;
use App\Modules\Catalog\Filters\QueryFilterInterface;
use App\Modules\Catalog\Models\Course;
use Illuminate\Database\Eloquent\Builder;

/**
 * Exact category (subcategories are not included).
 *
 * @implements QueryFilterInterface<Course, CourseFilterDTO>
 */
final class CategoryIdFilter implements QueryFilterInterface
{
    public function apply(Builder $query, object $filter): void
    {
        if ($filter->category_id === null) {
            return;
        }

        $query->where('category_id', $filter->category_id);
    }
}
