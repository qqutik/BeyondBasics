<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use App\Shared\Enums\CurrencyEnum;
use DomainException;

/**
 * Thrown when two Money values in different currencies meet in one operation (add, compare...).
 * There is no currency conversion: mixing currencies is always a bug in the caller.
 */
final class CurrencyMismatchException extends DomainException
{
    public static function between(CurrencyEnum $expected, CurrencyEnum $actual): self
    {
        return new self("Currency mismatch: expected {$expected->value}, got {$actual->value}.");
    }
}
