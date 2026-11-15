<?php

declare(strict_types=1);

namespace App\Modules\Billing\Services;

use App\Modules\Billing\DTO\Order\OrderFilterDTO;
use App\Modules\Billing\Models\Order;
use App\Modules\Billing\Repositories\OrderRepository;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class OrderService
{
    public function __construct(private OrderRepository $repository) {}

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function findAll(OrderFilterDTO $DTO, int $userId): LengthAwarePaginator
    {
        return $this->repository->findAll($DTO, $userId);
    }

    public function findOneByUuid(string $uuid, int $userId): Order
    {
        return $this->repository->findOneByUuid($uuid, $userId);
    }
}
