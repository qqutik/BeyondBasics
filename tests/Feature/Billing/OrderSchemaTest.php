<?php

declare(strict_types=1);

use App\Modules\Billing\Enums\OrderStatusEnum;
use App\Modules\Billing\Enums\PaymentProviderEnum;
use App\Modules\Billing\Models\Order;
use App\Modules\Identity\Models\User;
use App\Shared\Enums\CurrencyEnum;
use Illuminate\Database\QueryException;

/**
 * @param  array<string, mixed>  $attributes
 */
function billingOrder(array $attributes = [], OrderStatusEnum $status = OrderStatusEnum::PENDING): Order
{
    $order = new Order(array_merge([
        'provider' => PaymentProviderEnum::STRIPE,
        'user_id' => User::factory()->create()->id,
        'total' => 4498,
        'subtotal' => 4998,
        'discount' => 500,
        'currency' => CurrencyEnum::USD,
    ], $attributes));

    // status and paid_at are not fillable: the checkout / webhook workflow assigns them explicitly.
    $order->status = $status;
    $order->paid_at = $status->wasPaid() ? now() : null;
    $order->save();

    return $order;
}

it('creates a pending order with a uuid and Money totals', function () {
    $order = billingOrder()->refresh();

    expect($order->uuid)->toBeUuid()
        ->and($order->provider)->toBe(PaymentProviderEnum::STRIPE)
        ->and($order->status)->toBe(OrderStatusEnum::PENDING)
        ->and($order->isPending())->toBeTrue()
        ->and($order->currency)->toBe(CurrencyEnum::USD)
        ->and($order->total)->toBe(4498)
        ->and($order->subtotalMoney()->amount())->toBe(4998)
        ->and($order->discountMoney()->amount())->toBe(500)
        ->and($order->totalMoney()->format())->toBe('$44.98')
        ->and($order->payment_id)->toBeNull()
        ->and($order->checkout_id)->toBeNull()
        ->and(array_key_exists('id', $order->toArray()))->toBeFalse();
});

it('does not mass-assign status, paid_at or provider ids', function () {
    $order = new Order([
        'status' => 'success',
        'paid_at' => now(),
        'payment_id' => 'pi_test',
        'checkout_id' => 'cs_test',
    ]);

    expect($order->status)->toBe(OrderStatusEnum::PENDING)
        ->and($order->paid_at)->toBeNull()
        ->and($order->payment_id)->toBeNull()
        ->and($order->checkout_id)->toBeNull();
});

it('rejects a total that does not equal subtotal minus discount', function () {
    billingOrder(['total' => 4998]);
})->throws(QueryException::class, 'orders_total_matches');

it('rejects negative amounts', function () {
    billingOrder(['subtotal' => 100, 'discount' => 200, 'total' => -100]);
})->throws(QueryException::class, 'orders_amounts_non_negative');

it('rejects an unknown status', function () {
    $order = billingOrder();

    DB::table('orders')->where('id', $order->id)->update(['status' => 'shipped']);
})->throws(QueryException::class, 'orders_status_valid');

it('rejects a paid order without paid_at', function () {
    $order = billingOrder();

    DB::table('orders')->where('id', $order->id)->update(['status' => 'success', 'paid_at' => null]);
})->throws(QueryException::class, 'orders_paid_at_when_paid');

it('allows only one pending order per user', function () {
    $first = billingOrder();

    billingOrder(['user_id' => $first->user_id]);
})->throws(QueryException::class, 'orders_one_pending_per_user');

it('allows a new pending order once the previous one is no longer pending', function () {
    $first = billingOrder(status: OrderStatusEnum::CANCELED);
    billingOrder(['user_id' => $first->user_id], OrderStatusEnum::SUCCESS);

    $next = billingOrder(['user_id' => $first->user_id]);

    expect($next->exists)->toBeTrue()
        ->and(Order::query()->where('user_id', $first->user_id)->count())->toBe(3);
});

it('counts a coupon once per user while it is held or used', function (OrderStatusEnum $previous) {
    $first = billingOrder(['coupon_id' => 7], $previous);

    billingOrder(['user_id' => $first->user_id, 'coupon_id' => 7]);
})->with([
    'pending' => OrderStatusEnum::PENDING,
    'success' => OrderStatusEnum::SUCCESS,
    'refunded' => OrderStatusEnum::REFUNDED,
])->throws(QueryException::class);

it('releases the coupon when the order was abandoned', function (OrderStatusEnum $previous) {
    $first = billingOrder(['coupon_id' => 7], $previous);

    $next = billingOrder(['user_id' => $first->user_id, 'coupon_id' => 7]);

    expect($next->exists)->toBeTrue();
})->with([
    'failed' => OrderStatusEnum::FAILED,
    'expired' => OrderStatusEnum::EXPIRED,
    'canceled' => OrderStatusEnum::CANCELED,
]);

it('lets different users use the same coupon', function () {
    billingOrder(['coupon_id' => 7]);
    $other = billingOrder(['coupon_id' => 7]);

    expect($other->exists)->toBeTrue();
});

it('keeps provider ids unique per provider', function (string $column) {
    $first = billingOrder(status: OrderStatusEnum::SUCCESS);
    $first->{$column} = 'same_id';
    $first->save();

    $second = billingOrder(status: OrderStatusEnum::SUCCESS);
    $second->{$column} = 'same_id';
    $second->save();
})->with(['payment_id', 'checkout_id'])->throws(QueryException::class, 'unique');

it('lets many orders wait for their provider ids', function () {
    billingOrder(status: OrderStatusEnum::FAILED);
    billingOrder(status: OrderStatusEnum::FAILED);

    expect(Order::query()->whereNull('payment_id')->whereNull('checkout_id')->count())->toBe(2);
});

it('keeps the orders table reversible', function () {
    Artisan::call('migrate:rollback', ['--path' => 'app/Modules/Billing/Database/Migrations']);

    expect(Schema::hasTable('orders'))->toBeFalse();

    Artisan::call('migrate', ['--path' => 'app/Modules/Billing/Database/Migrations']);

    expect(Schema::hasTable('orders'))->toBeTrue();
});
