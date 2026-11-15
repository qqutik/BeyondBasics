<?php

declare(strict_types=1);

namespace App\Modules\Billing\Filters\Order;

use App\Modules\Billing\DTO\Order\OrderFilterDTO;
use App\Modules\Billing\Filters\QueryFilterInterface;
use App\Modules\Billing\Models\Order;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;

final readonly class OrderFilters
{
    /**
     * @var list<class-string<QueryFilterInterface<Order, OrderFilterDTO>>>
     */
    private const array FILTERS = [
        StatusFilter::class,
        CurrencyFilter::class,
        CreatedAtFilter::class,
    ];

    public function __construct(
        private Container $container,
    ) {}

    /**
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function apply(Builder $query, OrderFilterDTO $filter): Builder
    {
        foreach (self::FILTERS as $class) {
            $this->container->make($class)->apply($query, $filter);
        }

        return $query;
    }
}
