<?php

declare(strict_types=1);

namespace App\Modules\Billing\Enums;

/**
 * How coupons.value is read. The DB CHECK on coupons lists the same values.
 *
 *  - PERCENT: value is whole percent 1..100, the coupon has no currency
 *  - FIXED:   value is an amount in minor units (cents) of coupons.currency
 */
enum CouponTypeEnum: string
{
    case PERCENT = 'percent';
    case FIXED = 'fixed';
}
