<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTO\Course;

use App\Modules\Catalog\DTO\BaseDTO;
use DateTimeImmutable;

/**
 * Structure of one published version of a course, read from course_versions.snapshot.
 */
final class CourseStructureDTO extends BaseDTO
{
    /**
     * @param  list<CourseStructureSectionDTO>  $sections
     */
    public function __construct(
        public readonly int $courseId,
        public readonly int $versionId,
        public readonly int $version,
        public readonly DateTimeImmutable $publishedAt,
        public readonly string $title,
        public readonly array $sections,
    ) {}

    public function findLesson(int $lessonId): ?CourseStructureLessonDTO
    {
        foreach ($this->sections as $section) {
            foreach ($section->lessons as $lesson) {
                if ($lesson->id === $lessonId) {
                    return $lesson;
                }
            }
        }

        return null;
    }

    public function totalDurationSec(): int
    {
        $total = 0;

        foreach ($this->sections as $section) {
            foreach ($section->lessons as $lesson) {
                $total += $lesson->durationSec;
            }
        }

        return $total;
    }
}
