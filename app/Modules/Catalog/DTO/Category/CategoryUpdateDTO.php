<?php


namespace App\Modules\Catalog\DTO\Category;

 use App\Modules\Catalog\DTO\BaseDTO;

 final class CategoryUpdateDTO extends BaseDTO
{
    public function __construct(
        public readonly int|null    $parent_id,
        public readonly string|null $name,
        public readonly bool|null   $is_active,
        public readonly int|null    $position,
    ) {}
}
