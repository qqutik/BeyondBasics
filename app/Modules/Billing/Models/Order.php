<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use App\Modules\Billing\Enums\OrderStatusEnum;
use App\Modules\Billing\Enums\PaymentProviderEnum;
use App\Shared\Enums\CurrencyEnum;
use App\Shared\ValueObjects\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid Public identifier, safe to expose (the numeric id is not).
 * @property PaymentProviderEnum $provider Payment processor of the order
 * @property int $user_id Buyer (Identity user). DB-level FK only: no Eloquent relation across modules.
 * @property string|null $payment_id Payment id in the provider's system (Stripe PaymentIntent), from the webhook
 * @property string|null $checkout_id Checkout session id in the provider's system
 * @property OrderStatusEnum $status
 * @property int $total Minor units: subtotal - discount, what the buyer pays
 * @property int $subtotal Minor units: sum of item prices before the discount
 * @property int $discount Minor units
 * @property CurrencyEnum $currency ISO 4217, one currency for the whole order
 * @property int|null $coupon_id
 * @property Carbon|null $paid_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['provider', 'user_id', 'total', 'subtotal', 'discount', 'currency', 'coupon_id'])]
#[Hidden(['id'])]
final class Order extends Model
{
    use HasUuids;

    /**
     * Same defaults as the DB, so a new (unsaved) model already reports status PENDING.
     *
     * Note: status, paid_at, payment_id and checkout_id are deliberately NOT fillable. They change only
     * through the checkout / webhook workflow (assigned explicitly in the service).
     */
    protected $attributes = [
        'status' => 'pending',
        'discount' => 0,
    ];

    /**
     * Columns filled with a UUID on create. The primary key stays an auto-increment bigint.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function totalMoney(): Money
    {
        return Money::of($this->total, $this->currency);
    }

    public function subtotalMoney(): Money
    {
        return Money::of($this->subtotal, $this->currency);
    }

    public function discountMoney(): Money
    {
        return Money::of($this->discount, $this->currency);
    }

    public function isPending(): bool
    {
        return $this->status === OrderStatusEnum::PENDING;
    }

    /**
     * @param  Builder<Order>  $query
     */
    #[Scope]
    protected function byUserId(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => PaymentProviderEnum::class,
            'status' => OrderStatusEnum::class,
            'total' => 'integer',
            'subtotal' => 'integer',
            'discount' => 'integer',
            'currency' => CurrencyEnum::class,
            'coupon_id' => 'integer',
            'paid_at' => 'datetime',
        ];
    }
}
