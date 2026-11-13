<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTO\Course;

use App\Modules\Catalog\DTO\BaseDTO;

final class CourseStoreDTO extends BaseDTO
{
    private string $slug;

    public function __construct(
        public readonly int $user_id,
        public readonly int $category_id,

        public readonly string $title,
        public readonly ?string $subtitle,
        public readonly ?string $description,

        public readonly int $price,
        public readonly string $currency,
        public readonly string $level,
        public readonly string $language,
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
