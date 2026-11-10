<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTO\Category;

use App\Modules\Catalog\DTO\BaseDTO;

final class CategoryUpdateDTO extends BaseDTO
{
    public private(set) ?int $parent_id;

    public private(set) string $name;

    public private(set) bool $is_active;

    public private(set) int $position;

    /**
     * @param  array<string, mixed>  $data  validated input, only the keys that were sent
     */
    public static function fromArray(array $data): self
    {
        $dto = new self;

        if (array_key_exists('parent_id', $data)) {
            $dto->parent_id = is_numeric($data['parent_id']) ? (int) $data['parent_id'] : null;
        }

        if (array_key_exists('name', $data) && is_string($data['name'])) {
            $dto->name = $data['name'];
        }

        if (array_key_exists('is_active', $data)) {
            $dto->is_active = filter_var($data['is_active'], FILTER_VALIDATE_BOOL);
        }

        if (array_key_exists('position', $data) && is_numeric($data['position'])) {
            $dto->position = (int) $data['position'];
        }

        return $dto;
    }

    public function has(string $field): bool
    {
        return array_key_exists($field, $this->toArray());
    }
}
