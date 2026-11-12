<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTO\Course;

use App\Modules\Catalog\DTO\BaseDTO;

/**
 * New structure of a course: sections in order, each with its lessons in order.
 */
final class CurriculumDTO extends BaseDTO
{
    /**
     * @param  list<CurriculumSectionDTO>  $sections
     */
    public function __construct(
        public readonly array $sections,
    ) {}

    /**
     * @param  list<array{id: int|string, lessons: list<int|string>}>  $sections  validated input
     */
    public static function fromArray(array $sections): self
    {
        return new self(array_map(
            static fn (array $section): CurriculumSectionDTO => new CurriculumSectionDTO(
                id: (int) $section['id'],
                lessons: array_map(intval(...), $section['lessons']),
            ),
            $sections,
        ));
    }

    /**
     * @return list<int>
     */
    public function sectionIds(): array
    {
        return array_map(static fn (CurriculumSectionDTO $section): int => $section->id, $this->sections);
    }

    /**
     * @return list<int>
     */
    public function lessonIds(): array
    {
        return array_merge(...array_map(static fn (CurriculumSectionDTO $section): array => $section->lessons, $this->sections));
    }
}
