<?php

declare(strict_types=1);

namespace App\Modules\Billing\Http\Controllers;

use App\Modules\Billing\Http\Requests\OrderIndexRequest;
use App\Modules\Billing\Http\Resources\OrderResource;
use App\Modules\Billing\Services\OrderService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

/**
 * The buyer's own orders. Both routes are behind auth:api, and every query is scoped to the current user.
 */
final readonly class OrderController
{
    public function __construct(private OrderService $service) {}

    public function index(OrderIndexRequest $request): AnonymousResourceCollection
    {
        return OrderResource::collection($this->service->findAll($request->toDTO(), $this->userId()));
    }

    public function show(string $uuid): OrderResource
    {
        return new OrderResource($this->service->findOneByUuid($uuid, $this->userId()));
    }

    /**
     * Same as in Catalog: the guard returns int|string|null, the routes guarantee a logged-in user.
     */
    private function userId(): int
    {
        $id = Auth::id();

        return is_numeric($id) ? (int) $id : 0;
    }
}
