<?php

declare(strict_types=1);

namespace App\Shared\ValueObjects;

use App\Shared\Enums\CurrencyEnum;
use App\Shared\Exceptions\CurrencyMismatchException;
use InvalidArgumentException;

/**
 * Amount in integer minor units (cents) + currency. Immutable: every operation returns a new instance.
 *
 * No floats anywhere. Negative amounts are allowed (refunds in a ledger are negative);
 * "price cannot be negative" is a rule of the place that stores the value, not of Money itself.
 */
final readonly class Money
{
    /** 100% in basis points: 2000 bps = 20%. */
    public const int FULL_BASIS_POINTS = 10_000;

    private function __construct(
        private int $amount,
        private CurrencyEnum $currency,
    ) {}

    public static function of(int $amount, CurrencyEnum $currency): self
    {
        return new self($amount, $currency);
    }

    public static function zero(CurrencyEnum $currency): self
    {
        return new self(0, $currency);
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): CurrencyEnum
    {
        return $this->currency;
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount - $other->amount, $this->currency);
    }

    /**
     * Share of the amount in basis points, rounded half away from zero to a whole minor unit:
     * Money::of(1999, USD)->percentage(2000) is 400 (399.8 rounded).
     */
    public function percentage(int $basisPoints): self
    {
        if ($basisPoints < 0) {
            throw new InvalidArgumentException('Basis points cannot be negative.');
        }

        $absolute = intdiv(abs($this->amount) * $basisPoints + intdiv(self::FULL_BASIS_POINTS, 2), self::FULL_BASIS_POINTS);

        return new self($this->amount < 0 ? -$absolute : $absolute, $this->currency);
    }

    /**
     * Splits the amount by ratios without losing a minor unit: the parts always add up to the original.
     * The remainder goes one unit at a time to the first parts with a non-zero ratio.
     * Keys are kept, so a discount can be split straight by order item id.
     *
     *   Money::of(100, USD)->allocate([1, 1, 1])  → 34, 33, 33
     *   Money::of(1000, USD)->allocate([8000, 2000]) → 800, 200  (author / platform)
     *   $discount->allocate([$itemA => 1999, $itemB => 2999]) → [$itemA => Money, $itemB => Money]
     *
     * @template TKey of array-key
     *
     * @param  array<TKey, int>  $ratios  non-negative, at least one above zero
     * @return array<TKey, Money> same keys and order as $ratios
     */
    public function allocate(array $ratios): array
    {
        if ($ratios === []) {
            throw new InvalidArgumentException('Cannot allocate to an empty list of ratios.');
        }

        foreach ($ratios as $ratio) {
            if ($ratio < 0) {
                throw new InvalidArgumentException('Ratios cannot be negative.');
            }
        }

        $total = array_sum($ratios);

        if ($total === 0) {
            throw new InvalidArgumentException('At least one ratio must be above zero.');
        }

        $sign = $this->amount < 0 ? -1 : 1;
        $absolute = abs($this->amount);

        $parts = array_map(static fn (int $ratio): int => intdiv($absolute * $ratio, $total), $ratios);
        $remainder = $absolute - array_sum($parts);

        foreach ($ratios as $index => $ratio) {
            if ($remainder === 0) {
                break;
            }

            if ($ratio > 0) {
                $parts[$index]++;
                $remainder--;
            }
        }

        return array_map(fn (int $part): self => new self($sign * $part, $this->currency), $parts);
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    /**
     * Different currencies are simply not equal, no exception.
     */
    public function equals(Money $other): bool
    {
        return $this->currency === $other->currency && $this->amount === $other->amount;
    }

    public function greaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount > $other->amount;
    }

    public function lessThan(Money $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount < $other->amount;
    }

    /**
     * Display only, never for calculations: Money::of(1999, USD)->format() === '$19.99'.
     */
    public function format(): string
    {
        return $this->currency->format($this->amount);
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw CurrencyMismatchException::between($this->currency, $other->currency);
        }
    }
}
