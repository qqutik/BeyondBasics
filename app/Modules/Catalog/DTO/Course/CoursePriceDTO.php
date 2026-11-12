<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTO\Course;

use App\Modules\Catalog\DTO\BaseDTO;
use App\Modules\Catalog\Enums\CurrencyEnum;

/**
 * Money is never a float: amount in minor units + currency. 1999 USD = $19.99.
 */
final class CoursePriceDTO extends BaseDTO
{
    public function __construct(
        public readonly int $amount,
        public readonly CurrencyEnum $currency,
    ) {}

    public function isFree(): bool
    {
        return $this->amount === 0;
    }

    /**
     * Display only, never for calculations.
     */
    public function formatted(): string
    {
        return $this->currency->format($this->amount);
    }
}
