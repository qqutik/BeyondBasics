<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTO\Course;

use App\Modules\Catalog\DTO\BaseDTO;
use App\Shared\ValueObjects\Money;

/**
 * Current price of a course. Money is never a float: minor units + currency, 1999 USD = $19.99.
 */
final class CoursePriceDTO extends BaseDTO
{
    public function __construct(
        public readonly Money $price,
    ) {}

    public function isFree(): bool
    {
        return $this->price->isZero();
    }

    /**
     * Display only, never for calculations.
     */
    public function formatted(): string
    {
        return $this->price->format();
    }
}
