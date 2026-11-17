<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use App\Modules\Billing\Enums\CouponTypeEnum;
use App\Shared\Enums\CurrencyEnum;
use App\Shared\Exceptions\CurrencyMismatchException;
use App\Shared\ValueObjects\Money;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code Upper case, what the buyer types
 * @property CouponTypeEnum $type
 * @property int $value PERCENT: whole percent 1..100. FIXED: minor units of $currency.
 * @property CurrencyEnum|null $currency Only for FIXED coupons
 * @property int|null $max_uses NULL = unlimited
 * @property int $used_count
 * @property Carbon|null $valid_from
 * @property Carbon|null $expires_at
 * @property int|null $course_id Catalog course. DB-level FK only: no Eloquent relation across modules.
 * @property int|null $user_id Identity user (author). DB-level FK only.
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'code', 'type', 'value', 'currency', 'max_uses', 'valid_from', 'expires_at', 'course_id', 'user_id',
])]
final class Coupon extends Model
{
    /**
     * Note: used_count is deliberately NOT fillable. It changes only through an atomic UPDATE
     * in the service (reserve on checkout, release on failed / expired orders).
     */
    protected $attributes = [
        'used_count' => 0,
    ];

    /**
     * Is the coupon inside its validity window at $moment? Says nothing about remaining uses.
     */
    public function isValidAt(CarbonInterface $moment): bool
    {
        if ($this->valid_from !== null && $moment->lt($this->valid_from)) {
            return false;
        }

        return $this->expires_at === null || $moment->lt($this->expires_at);
    }

    /**
     * Read-only hint for validation messages. The real check is the atomic UPDATE: two buyers can both
     * see "1 use left" here, only one of them gets it.
     */
    public function hasUsesLeft(): bool
    {
        return $this->max_uses === null || $this->used_count < $this->max_uses;
    }

    /**
     * Discount this coupon gives on $price. Never more than the price itself, so the total cannot go negative.
     *
     * @throws CurrencyMismatchException a FIXED coupon in another currency than the price
     */
    public function discountFor(Money $price): Money
    {
        if ($this->type === CouponTypeEnum::PERCENT) {
            return $price->percentage($this->value * 100);
        }

        $discount = Money::of($this->value, $this->currency ?? $price->currency());

        return $discount->greaterThan($price) ? $price : $discount;
    }

    /**
     * Codes are case-insensitive for the buyer: always stored and looked up in upper case.
     *
     * @return Attribute<string, string>
     */
    protected function code(): Attribute
    {
        return Attribute::make(
            set: static fn (string $value): string => mb_strtoupper(trim($value)),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CouponTypeEnum::class,
            'value' => 'integer',
            'currency' => CurrencyEnum::class,
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'valid_from' => 'datetime',
            'expires_at' => 'datetime',
            'course_id' => 'integer',
            'user_id' => 'integer',
        ];
    }
}
