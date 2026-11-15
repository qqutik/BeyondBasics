<?php

declare(strict_types=1);

namespace App\Modules\Billing\Repositories;

use App\Modules\Billing\DTO\Order\OrderFilterDTO;
use App\Modules\Billing\Filters\Order\OrderFilters;
use App\Modules\Billing\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class OrderRepository
{
    public function __construct(
        private OrderFilters $filters,
    ) {}

    /**
     * Orders of one user, newest first. The user scope is not a filter: it is always applied.
     *
     * @return LengthAwarePaginator<int, Order>
     */
    public function findAll(OrderFilterDTO $filterDTO, int $userId): LengthAwarePaginator
    {
        return $this->filters
            ->apply(Order::query()->byUserId($userId), $filterDTO)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($filterDTO->perPage)
            ->withQueryString();
    }

    /**
     * 404 when the order does not exist or belongs to another user (never reveals someone else's order).
     */
    public function findOneByUuid(string $uuid, int $userId): Order
    {
        return Order::query()
            ->byUserId($userId)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
