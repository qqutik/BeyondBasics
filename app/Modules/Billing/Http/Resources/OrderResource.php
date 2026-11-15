<?php

declare(strict_types=1);

namespace App\Modules\Billing\Http\Resources;

use App\Modules\Billing\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;

        return [
            'uuid' => $order->uuid,
            'provider' => $order->provider,
            'total' => $order->total,
            'subtotal' => $order->subtotal,
            'discount' => $order->discount,
            'currency' => $order->currency,
            //            'coupon_id' => $order->coupon_id,
        ];
    }
}
