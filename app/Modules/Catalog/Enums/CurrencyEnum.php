<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

/**
 * Supported currencies, ISO 4217 codes (upper case, matches the DB CHECK on courses.currency).
 * Amounts are always stored in minor units (cents, kopiyky), see minorUnits().
 */
enum CurrencyEnum: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case UAH = 'UAH';

    /**
     * How many minor units make one major unit: 100 cents = 1 USD.
     * All three currencies use 2 decimals; kept explicit because some (e.g. JPY) use 0.
     */
    public function minorUnits(): int
    {
        return match ($this) {
            self::USD, self::EUR, self::UAH => 100,
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::USD => '$',
            self::EUR => '€',
            self::UAH => '₴',
        };
    }

    /**
     * Human-readable amount for display only: CurrencyEnum::USD->format(1999) === '$19.99'.
     * Never use the result in calculations.
     */
    public function format(int $amount): string
    {
        $decimals = (int) log10($this->minorUnits());

        return $this->symbol().number_format($amount / $this->minorUnits(), $decimals, '.', '');
    }
}
