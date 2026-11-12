<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTO\Course;

use App\Modules\Catalog\DTO\BaseDTO;

final class CourseStructureSectionDTO extends BaseDTO
{
    /**
     * @param  list<CourseStructureLessonDTO>  $lessons
     */
    public function __construct(
        public readonly int $id,
        public readonly int $position,
        public readonly string $title,
        public readonly array $lessons,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data  one section of course_versions.snapshot
     */
    public static function fromSnapshot(array $data): self
    {
        $lessons = is_array($data['lessons'] ?? null) ? $data['lessons'] : [];

        return new self(
            id: is_numeric($data['id'] ?? null) ? (int) $data['id'] : 0,
            position: is_numeric($data['position'] ?? null) ? (int) $data['position'] : 0,
            title: is_string($data['title'] ?? null) ? $data['title'] : '',
            lessons: array_values(array_map(
                static fn (array $lesson): CourseStructureLessonDTO => CourseStructureLessonDTO::fromSnapshot($lesson),
                array_filter($lessons, is_array(...)),
            )),
        );
    }
}
