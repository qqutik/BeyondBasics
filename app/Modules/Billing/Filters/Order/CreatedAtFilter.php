<?php

declare(strict_types=1);

namespace App\Modules\Billing\Filters\Order;

use App\Modules\Billing\DTO\Order\OrderFilterDTO;
use App\Modules\Billing\Filters\QueryFilterInterface;
use App\Modules\Billing\Models\Order;
use Illuminate\Database\Eloquent\Builder;

/**
 * Orders created within a date range. Both ends are optional and inclusive (whole days).
 *
 * @implements QueryFilterInterface<Order, OrderFilterDTO>
 */
final class CreatedAtFilter implements QueryFilterInterface
{
    public function apply(Builder $query, object $filter): void
    {
        if ($filter->date_from !== null) {
            $query->whereDate('created_at', '>=', $filter->date_from);
        }

        if ($filter->date_to !== null) {
            $query->whereDate('created_at', '<=', $filter->date_to);
        }
    }
}
