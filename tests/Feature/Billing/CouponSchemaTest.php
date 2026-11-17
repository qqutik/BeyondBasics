<?php

declare(strict_types=1);

use App\Modules\Billing\Enums\CouponTypeEnum;
use App\Modules\Billing\Models\Coupon;
use App\Modules\Identity\Models\User;
use App\Shared\Enums\CurrencyEnum;
use App\Shared\Exceptions\CurrencyMismatchException;
use App\Shared\ValueObjects\Money;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

it('creates a percent coupon with defaults and an upper case code', function () {
    $coupon = billingCoupon(['code' => '  summer10 '])->refresh();

    expect($coupon->code)->toBe('SUMMER10')
        ->and($coupon->type)->toBe(CouponTypeEnum::PERCENT)
        ->and($coupon->value)->toBe(10)
        ->and($coupon->currency)->toBeNull()
        ->and($coupon->used_count)->toBe(0)
        ->and($coupon->max_uses)->toBeNull();
});

it('creates a fixed coupon in a currency', function () {
    $coupon = billingCoupon(['type' => CouponTypeEnum::FIXED, 'value' => 500, 'currency' => CurrencyEnum::EUR])->refresh();

    expect($coupon->currency)->toBe(CurrencyEnum::EUR)
        ->and($coupon->value)->toBe(500);
});

it('does not mass-assign used_count', function () {
    expect((new Coupon(['used_count' => 99]))->used_count)->toBe(0);
});

it('keeps codes unique regardless of case', function () {
    billingCoupon(['code' => 'BLACKFRIDAY']);
    billingCoupon(['code' => 'blackfriday']);
})->throws(QueryException::class, 'coupons_code_unique');

it('rejects a malformed code', function (string $code) {
    $coupon = billingCoupon();

    DB::table('coupons')->where('id', $coupon->id)->update(['code' => $code]);
})->with(['lower case' => 'summer', 'too short' => 'AB', 'space' => 'BLACK FRIDAY'])
    ->throws(QueryException::class, 'coupons_code_format');

it('rejects a value that does not fit the type', function (CouponTypeEnum $type, int $value, ?CurrencyEnum $currency) {
    billingCoupon(['type' => $type, 'value' => $value, 'currency' => $currency]);
})->with([
    'percent over 100' => [CouponTypeEnum::PERCENT, 101, null],
    'percent zero' => [CouponTypeEnum::PERCENT, 0, null],
    'percent with currency' => [CouponTypeEnum::PERCENT, 10, CurrencyEnum::USD],
    'fixed without currency' => [CouponTypeEnum::FIXED, 500, null],
    'fixed zero' => [CouponTypeEnum::FIXED, 0, CurrencyEnum::USD],
])->throws(QueryException::class, 'coupons_type_value');

it('rejects an unknown type', function () {
    $coupon = billingCoupon();

    DB::table('coupons')->where('id', $coupon->id)->update(['type' => 'bogo']);
})->throws(QueryException::class, 'coupons_type_value');

it('never lets used_count go past max_uses or below zero', function (string $column, int $value) {
    $coupon = billingCoupon(['max_uses' => 10]);

    DB::table('coupons')->where('id', $coupon->id)->update([$column => $value]);
})->with([
    'over max' => ['used_count', 11],
    'negative' => ['used_count', -1],
    'zero max' => ['max_uses', 0],
])->throws(QueryException::class, 'coupons_uses_valid');

it('rejects expires_at before valid_from', function () {
    billingCoupon(['valid_from' => now(), 'expires_at' => now()->subDay()]);
})->throws(QueryException::class, 'coupons_dates_order');

it('points to an existing course and author', function (string $column) {
    billingCoupon([$column => 999999]);
})->with(['course_id', 'user_id'])->throws(QueryException::class, 'foreign key');

it('accepts an author coupon', function () {
    $author = User::factory()->create();

    expect(billingCoupon(['user_id' => $author->id])->user_id)->toBe($author->id);
});

it('links orders to an existing coupon only', function () {
    billingOrder(['coupon_id' => 999999]);
})->throws(QueryException::class, 'orders_coupon_id_foreign');

it('cannot delete a coupon that an order used', function () {
    $coupon = billingCoupon();
    billingOrder(['coupon_id' => $coupon->id]);

    $coupon->delete();
})->throws(QueryException::class, 'orders_coupon_id_foreign');

it('knows its validity window', function () {
    $coupon = billingCoupon([
        'valid_from' => Carbon::parse('2026-12-01 00:00'),
        'expires_at' => Carbon::parse('2026-12-31 00:00'),
    ]);

    expect($coupon->isValidAt(Carbon::parse('2026-11-30 23:59')))->toBeFalse()
        ->and($coupon->isValidAt(Carbon::parse('2026-12-01 00:00')))->toBeTrue()
        ->and($coupon->isValidAt(Carbon::parse('2026-12-30 12:00')))->toBeTrue()
        ->and($coupon->isValidAt(Carbon::parse('2026-12-31 00:00')))->toBeFalse()
        ->and(billingCoupon()->isValidAt(now()))->toBeTrue();
});

it('knows if uses are left', function () {
    $limited = billingCoupon(['max_uses' => 1]);

    expect($limited->hasUsesLeft())->toBeTrue()
        ->and(billingCoupon()->hasUsesLeft())->toBeTrue();

    DB::table('coupons')->where('id', $limited->id)->update(['used_count' => 1]);

    expect($limited->refresh()->hasUsesLeft())->toBeFalse();
});

it('calculates a percent discount', function () {
    $coupon = billingCoupon(['value' => 15]);

    expect($coupon->discountFor(Money::of(1999, CurrencyEnum::USD))->amount())->toBe(300)
        ->and($coupon->discountFor(Money::of(1999, CurrencyEnum::UAH))->currency())->toBe(CurrencyEnum::UAH);
});

it('caps a fixed discount at the price', function () {
    $coupon = billingCoupon(['type' => CouponTypeEnum::FIXED, 'value' => 500, 'currency' => CurrencyEnum::USD]);

    expect($coupon->discountFor(Money::of(1999, CurrencyEnum::USD))->amount())->toBe(500)
        ->and($coupon->discountFor(Money::of(300, CurrencyEnum::USD))->amount())->toBe(300);
});

it('refuses a fixed coupon in another currency', function () {
    $coupon = billingCoupon(['type' => CouponTypeEnum::FIXED, 'value' => 500, 'currency' => CurrencyEnum::USD]);

    $coupon->discountFor(Money::of(1999, CurrencyEnum::EUR));
})->throws(CurrencyMismatchException::class);
