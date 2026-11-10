<?php


namespace App\Modules\Catalog\DTO\Category;

use App\Modules\Catalog\DTO\BaseDTO;

final class CategoryStoreDTO extends BaseDTO
{
    private string $slug;

    public function __construct(
        public readonly int|null $parent_id,
        public readonly string $name,
        public readonly bool $is_active,
        public readonly int $position,
    ) {}

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }
}
