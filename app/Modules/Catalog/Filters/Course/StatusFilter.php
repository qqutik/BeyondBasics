<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Filters\Course;

use App\Modules\Catalog\DTO\Course\CourseFilterDTO;
use App\Modules\Catalog\Filters\QueryFilterInterface;
use App\Modules\Catalog\Models\Course;
use Illuminate\Database\Eloquent\Builder;

/**
 * Status of the draft in the editor (draft / review / published / declined / archived).
 *
 * @implements QueryFilterInterface<Course, CourseFilterDTO>
 */
final class StatusFilter implements QueryFilterInterface
{
    public function apply(Builder $query, object $filter): void
    {
        if ($filter->status === null) {
            return;
        }

        $query->where('status', $filter->status->value);
    }
}
