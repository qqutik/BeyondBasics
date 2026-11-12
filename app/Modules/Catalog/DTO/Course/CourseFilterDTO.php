<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTO\Course;

use App\Modules\Catalog\DTO\BaseDTO;

final class CourseFilterDTO extends BaseDTO
{
    public function __construct(
        public readonly ?int $user_id,
        public readonly ?int $category_id,

        public readonly ?string $title,
        public readonly ?string $subtitle,
        public readonly ?string $description,

        public readonly ?int $price,

        public readonly ?string $level,
        public readonly ?string $language,
        public readonly ?string $status,

        public readonly int $perPage,
    ) {}
}
