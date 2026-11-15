<?php

declare(strict_types=1);

namespace App\Modules\Billing\Enums;

/**
 * Payment processor that handles an order. Stored in orders.provider.
 * payment_id / checkout_id of an order are ids in THIS provider's system.
 */
enum PaymentProviderEnum: string
{
    case STRIPE = 'stripe';
}
