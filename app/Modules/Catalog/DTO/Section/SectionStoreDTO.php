<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTO\Section;

use App\Modules\Catalog\DTO\BaseDTO;

final class SectionStoreDTO extends BaseDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $position,
    ) {}
}
