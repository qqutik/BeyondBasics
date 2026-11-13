<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Filters\Course;

use App\Modules\Catalog\DTO\Course\CourseFilterDTO;
use App\Modules\Catalog\Filters\QueryFilterInterface;
use App\Modules\Catalog\Models\Course;
use Illuminate\Database\Eloquent\Builder;

/**
 * Course currency. Use together with price_min / price_max: amounts are compared without conversion,
 * so a price range only makes sense within one currency.
 *
 * @implements QueryFilterInterface<Course, CourseFilterDTO>
 */
final class CurrencyFilter implements QueryFilterInterface
{
    public function apply(Builder $query, object $filter): void
    {
        if ($filter->currency === null) {
            return;
        }

        $query->where('currency', $filter->currency->value);
    }
}
