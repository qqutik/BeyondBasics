<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Filters\Course;

use App\Modules\Catalog\DTO\Course\CourseFilterDTO;
use App\Modules\Catalog\Filters\QueryFilterInterface;
use App\Modules\Catalog\Models\Course;
use Illuminate\Database\Eloquent\Builder;

/**
 * Course language.
 *
 * @implements QueryFilterInterface<Course, CourseFilterDTO>
 */
final class LanguageFilter implements QueryFilterInterface
{
    public function apply(Builder $query, object $filter): void
    {
        if ($filter->language === null) {
            return;
        }

        $query->where('language', $filter->language->value);
    }
}
