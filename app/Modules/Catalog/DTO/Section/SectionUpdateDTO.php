<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTO\Section;

use App\Modules\Catalog\DTO\BaseDTO;

/**
 * Update of a section: only the title can change.
 *
 * Deliberately NOT here:
 *  - course_id: comes from the route ({course}), a section never moves to another course;
 *  - position: changes only through the reorder endpoint, so the order has a single source of truth.
 */
final class SectionUpdateDTO extends BaseDTO
{
    public function __construct(
        public readonly string $title,
    ) {}
}
