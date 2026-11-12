<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Contracts;

use App\Modules\Catalog\DTO\Course\CourseDirectoryDTO;
use App\Modules\Catalog\DTO\Course\CoursePriceDTO;
use App\Modules\Catalog\DTO\Course\CourseStructureDTO;

/**
 * The only way other modules (Billing, Enrollment, Reviews...) read courses.
 * Returns DTOs, never models: callers cannot lazy-load relations or save a Catalog model.
 */
interface CourseDirectoryContract
{
    /**
     * null when the course does not exist or was deleted.
     */
    public function findOneById(int $courseId): ?CourseDirectoryDTO;

    /**
     * Missing ids are skipped, no exception.
     *
     * @param  list<int>  $courseIds
     * @return array<int, CourseDirectoryDTO> keyed by course id
     */
    public function findMany(array $courseIds): array;

    /**
     * Current price in minor units. null when the course does not exist.
     * Whether the course can be bought at all is a separate question: check findOneById()->isPublished().
     */
    public function getPrice(int $courseId): ?CoursePriceDTO;

    /**
     * Structure of a published version (the snapshot a student bought).
     * Works even after the course was deleted or edited: the snapshot never changes.
     * null when the version does not exist or belongs to another course.
     */
    public function getPublishedStructure(int $courseId, int $versionId): ?CourseStructureDTO;
}
