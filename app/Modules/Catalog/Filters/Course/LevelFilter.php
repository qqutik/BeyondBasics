<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Filters\Course;

use App\Modules\Catalog\DTO\Course\CourseFilterDTO;
use App\Modules\Catalog\Filters\QueryFilterInterface;
use App\Modules\Catalog\Models\Course;
use Illuminate\Database\Eloquent\Builder;

/**
 * Course level.
 *
 * @implements QueryFilterInterface<Course, CourseFilterDTO>
 */
final class LevelFilter implements QueryFilterInterface
{
    public function apply(Builder $query, object $filter): void
    {
        if ($filter->level === null) {
            return;
        }

        $query->where('level', $filter->level->value);
    }
}
