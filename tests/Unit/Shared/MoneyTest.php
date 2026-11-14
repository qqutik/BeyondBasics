<?php

declare(strict_types=1);

use App\Shared\Enums\CurrencyEnum;
use App\Shared\Exceptions\CurrencyMismatchException;
use App\Shared\ValueObjects\Money;

/**
 * @param  array<array-key, Money>  $parts
 * @return list<int>
 */
function amountsOf(array $parts): array
{
    return array_values(array_map(static fn (Money $part): int => $part->amount(), $parts));
}

it('adds and subtracts in the same currency', function () {
    $a = Money::of(1999, CurrencyEnum::USD);
    $b = Money::of(500, CurrencyEnum::USD);

    expect($a->add($b)->amount())->toBe(2499)
        ->and($a->subtract($b)->amount())->toBe(1499)
        ->and($b->subtract($a)->amount())->toBe(-1499)
        ->and($a->add($b)->currency())->toBe(CurrencyEnum::USD);
});

it('is immutable', function () {
    $a = Money::of(100, CurrencyEnum::USD);

    $a->add(Money::of(50, CurrencyEnum::USD));

    expect($a->amount())->toBe(100);
});

it('refuses to mix currencies', function (string $method) {
    $usd = Money::of(100, CurrencyEnum::USD);
    $eur = Money::of(100, CurrencyEnum::EUR);

    match ($method) {
        'add' => $usd->add($eur),
        'subtract' => $usd->subtract($eur),
        'greaterThan' => $usd->greaterThan($eur),
        'lessThan' => $usd->lessThan($eur),
        default => throw new LogicException("Unknown method {$method}"),
    };
})->with(['add', 'subtract', 'greaterThan', 'lessThan'])
    ->throws(CurrencyMismatchException::class, 'expected USD, got EUR');

it('treats different currencies as not equal without throwing', function () {
    expect(Money::of(100, CurrencyEnum::USD)->equals(Money::of(100, CurrencyEnum::EUR)))->toBeFalse()
        ->and(Money::of(100, CurrencyEnum::USD)->equals(Money::of(100, CurrencyEnum::USD)))->toBeTrue()
        ->and(Money::of(100, CurrencyEnum::USD)->equals(Money::of(101, CurrencyEnum::USD)))->toBeFalse();
});

it('compares amounts', function () {
    $small = Money::of(100, CurrencyEnum::UAH);
    $big = Money::of(200, CurrencyEnum::UAH);

    expect($big->greaterThan($small))->toBeTrue()
        ->and($small->lessThan($big))->toBeTrue()
        ->and($small->greaterThan($small))->toBeFalse();
});

it('knows its sign', function () {
    expect(Money::zero(CurrencyEnum::USD)->isZero())->toBeTrue()
        ->and(Money::of(1, CurrencyEnum::USD)->isPositive())->toBeTrue()
        ->and(Money::of(-1, CurrencyEnum::USD)->isNegative())->toBeTrue()
        ->and(Money::zero(CurrencyEnum::USD)->isPositive())->toBeFalse();
});

it('takes a percentage in basis points, rounding half away from zero', function (int $amount, int $bps, int $expected) {
    expect(Money::of($amount, CurrencyEnum::USD)->percentage($bps)->amount())->toBe($expected);
})->with([
    '20% of 19.99 = 3.998 → 4.00' => [1999, 2000, 400],
    '20% of 0.01 → 0' => [1, 2000, 0],
    '50% of 0.01 = 0.005 → 0.01' => [1, 5000, 1],
    '0%' => [1999, 0, 0],
    '100%' => [1999, Money::FULL_BASIS_POINTS, 1999],
    'negative mirrors positive' => [-1999, 2000, -400],
    'negative half rounds away from zero' => [-1, 5000, -1],
]);

it('refuses negative basis points', function () {
    Money::of(100, CurrencyEnum::USD)->percentage(-1);
})->throws(InvalidArgumentException::class);

// Dataset rows: amount, expected parts, then the ratios (variadic, so they arrive as list<int>).
it('allocates without losing a minor unit', function (int $amount, array $expected, int ...$ratios) {
    expect(amountsOf(Money::of($amount, CurrencyEnum::USD)->allocate($ratios)))->toBe($expected);
})->with([
    'even three-way split' => [100, [34, 33, 33], 1, 1, 1],
    'author / platform' => [1000, [800, 200], 8000, 2000],
    'odd cent goes first' => [1999, [1600, 399], 8000, 2000],
    'zero ratio gets nothing' => [100, [0, 50, 50], 0, 1, 1],
    'remainder skips zero ratios' => [101, [0, 51, 50], 0, 1, 1],
    'zero amount' => [0, [0, 0], 1, 2],
    'negative amount' => [-100, [-34, -33, -33], 1, 1, 1],
    'discount over cart by item prices' => [500, [167, 250, 83], 1999, 2999, 999],
]);

it('keeps the keys of the ratios', function () {
    $parts = Money::of(500, CurrencyEnum::USD)->allocate(['item-7' => 1999, 'item-9' => 2999]);

    expect(array_keys($parts))->toBe(['item-7', 'item-9'])
        ->and($parts['item-7']->amount() + $parts['item-9']->amount())->toBe(500);
});

it('keeps the sum of allocated parts equal to the original', function () {
    mt_srand(42);

    for ($i = 0; $i < 1000; $i++) {
        $amount = mt_rand(-1_000_000, 1_000_000);
        $ratios = array_map(static fn (): int => mt_rand(0, 10_000), range(1, mt_rand(1, 6)));
        $ratios[0] = max($ratios[0], 1);

        $parts = Money::of($amount, CurrencyEnum::EUR)->allocate($ratios);

        expect(array_sum(amountsOf($parts)))->toBe($amount)
            ->and($parts)->toHaveCount(count($ratios));
    }
});

it('refuses an empty list of ratios', function () {
    Money::of(100, CurrencyEnum::USD)->allocate([]);
})->throws(InvalidArgumentException::class, 'empty list');

it('refuses invalid ratios', function (string $message, int ...$ratios) {
    expect(fn () => Money::of(100, CurrencyEnum::USD)->allocate($ratios))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'all zero' => ['above zero', 0, 0],
    'negative' => ['cannot be negative', 1, -1],
]);

it('formats for display', function () {
    expect(Money::of(1999, CurrencyEnum::USD)->format())->toBe('$19.99')
        ->and(Money::of(2999, CurrencyEnum::EUR)->format())->toBe('€29.99');
});
