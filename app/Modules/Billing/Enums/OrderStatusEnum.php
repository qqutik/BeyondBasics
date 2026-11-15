<?php

declare(strict_types=1);

namespace App\Modules\Billing\Enums;

/**
 * Order lifecycle. The DB CHECK on orders.status lists the same values.
 *
 *   pending ──► success ──► partially_refunded ──► refunded
 *      │             └──────────────────────────────► refunded
 *      ├──► failed     (payment declined)
 *      ├──► expired    (Stripe session expired / never paid)
 *      └──► canceled   (replaced by a newer checkout of the same user)
 */
enum OrderStatusEnum: string
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case EXPIRED = 'expired';
    case CANCELED = 'canceled';
    case PARTIALLY_REFUNDED = 'partially_refunded';
    case REFUNDED = 'refunded';

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::SUCCESS, self::FAILED, self::EXPIRED, self::CANCELED],
            // Refunding one more item of a partially refunded order keeps it partially refunded.
            self::SUCCESS, self::PARTIALLY_REFUNDED => [self::PARTIALLY_REFUNDED, self::REFUNDED],
            self::FAILED, self::EXPIRED, self::CANCELED, self::REFUNDED => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /**
     * The order was paid at some point: the money moved, even if part of it came back.
     */
    public function wasPaid(): bool
    {
        return in_array($this, [self::SUCCESS, self::PARTIALLY_REFUNDED, self::REFUNDED], true);
    }

    /**
     * The order never got paid and never will: its coupon reservation is released.
     * Matches the "status NOT IN (...)" of the orders_coupon_once_per_user index.
     */
    public function isAbandoned(): bool
    {
        return in_array($this, [self::FAILED, self::EXPIRED, self::CANCELED], true);
    }
}
