<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Filters\Course;

use App\Modules\Catalog\DTO\Course\CourseFilterDTO;
use App\Modules\Catalog\Filters\QueryFilterInterface;
use App\Modules\Catalog\Models\Course;
use Illuminate\Database\Eloquent\Builder;

/**
 * Price range in minor units, both ends inclusive. Currency is not converted: 1000 USD and 1000 UAH both match 1000,
 * so the client should send currency together with the range (CurrencyFilter).
 *
 * @implements QueryFilterInterface<Course, CourseFilterDTO>
 */
final class PriceFilter implements QueryFilterInterface
{
    public function apply(Builder $query, object $filter): void
    {
        if ($filter->price_min !== null) {
            $query->where('price', '>=', $filter->price_min);
        }

        if ($filter->price_max !== null) {
            $query->where('price', '<=', $filter->price_max);
        }
    }
}
