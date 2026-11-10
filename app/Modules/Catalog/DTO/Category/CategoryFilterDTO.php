<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTO\Category;

use App\Modules\Catalog\DTO\BaseDTO;

final class CategoryFilterDTO extends BaseDTO
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?int    $parentId,
        public readonly ?bool   $isActive,
        public readonly int     $perPage,
    )
    {
    }
}
