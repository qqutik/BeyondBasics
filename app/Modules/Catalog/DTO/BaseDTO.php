<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTO;

use BackedEnum;
use Illuminate\Support\Str;
use ReflectionObject;
use ReflectionProperty;

/**
 * Base for all DTOs: toArray() returns every non-static property of the concrete DTO,
 * including private/protected ones, keyed in snake_case (accessToken => access_token).
 * Nested DTOs and arrays of DTOs are converted recursively; backed enums become their value.
 */
abstract class BaseDTO
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];

        foreach ((new ReflectionObject($this))->getProperties() as $property) {
            if ($property->isStatic() || ! $property->isInitialized($this)) {
                continue;
            }

            $result[Str::snake($property->getName())] = self::normalize($this->valueOf($property));
        }

        return $result;
    }

    private function valueOf(ReflectionProperty $property): mixed
    {
        return $property->getValue($this);
    }

    private static function normalize(mixed $value): mixed
    {
        return match (true) {
            $value instanceof self => $value->toArray(),
            $value instanceof BackedEnum => $value->value,
            is_array($value) => array_map(self::normalize(...), $value),
            default => $value,
        };
    }
}
