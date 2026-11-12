<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTO\Course;

use App\Modules\Catalog\DTO\BaseDTO;

final class CurriculumSectionDTO extends BaseDTO
{
    /**
     * @param  list<int>  $lessons  lesson ids in their new order inside this section
     */
    public function __construct(
        public readonly int $id,
        public readonly array $lessons,
    ) {}
}
