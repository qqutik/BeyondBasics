<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTO\Section;

use App\Modules\Catalog\DTO\BaseDTO;

final class SectionReorderDTO extends BaseDTO
{
    /**
     * @param  list<int>  $ids  section ids in the new order; ids[0] gets position 1
     */
    public function __construct(
        public readonly array $ids,
    ) {}
}
