<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Filters\Course;

use App\Modules\Catalog\DTO\Course\CourseFilterDTO;
use App\Modules\Catalog\Filters\QueryFilterInterface;
use App\Modules\Catalog\Models\Course;
use Illuminate\Database\Eloquent\Builder;

/**
 * Author of the course. For non-admins CourseIndexRequest always sets it to the current user.
 *
 * @implements QueryFilterInterface<Course, CourseFilterDTO>
 */
final class UserIdFilter implements QueryFilterInterface
{
    public function apply(Builder $query, object $filter): void
    {
        if ($filter->user_id === null) {
            return;
        }

        $query->where('user_id', $filter->user_id);
    }
}
