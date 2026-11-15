<?php

declare(strict_types=1);

namespace App\Modules\Billing\Filters\Order;

use App\Modules\Billing\DTO\Order\OrderFilterDTO;
use App\Modules\Billing\Filters\QueryFilterInterface;
use App\Modules\Billing\Models\Order;
use Illuminate\Database\Eloquent\Builder;

/**
 * Status of the draft in the editor (draft / review / published / declined / archived).
 *
 * @implements QueryFilterInterface<Order, OrderFilterDTO>
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
