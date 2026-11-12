<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Contracts\CourseDirectoryContract;
use App\Modules\Catalog\DTO\Course\CourseDirectoryDTO;
use App\Modules\Catalog\DTO\Course\CoursePriceDTO;
use App\Modules\Catalog\DTO\Course\CourseStructureDTO;
use App\Modules\Catalog\DTO\Course\CourseStructureSectionDTO;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\CourseVersion;
use App\Modules\Catalog\Repositories\CourseRepository;
use App\Modules\Catalog\Repositories\CourseVersionRepository;

/**
 * Implementation of the public Catalog contract. Maps models to DTOs at the module boundary.
 */
final readonly class CourseDirectoryService implements CourseDirectoryContract
{
    public function __construct(
        private CourseRepository $courseRepository,
        private CourseVersionRepository $courseVersionRepository,
    ) {}

    public function findOneById(int $courseId): ?CourseDirectoryDTO
    {
        $course = $this->courseRepository->findOneById($courseId);

        return $course !== null ? $this->toDTO($course) : null;
    }

    public function findMany(array $courseIds): array
    {
        if ($courseIds === []) {
            return [];
        }

        return $this->courseRepository
            ->findManyByIds(array_values(array_unique($courseIds)))
            ->mapWithKeys(fn (Course $course): array => [$course->id => $this->toDTO($course)])
            ->all();
    }

    public function getPrice(int $courseId): ?CoursePriceDTO
    {
        $course = $this->courseRepository->findOneById($courseId);

        return $course !== null ? new CoursePriceDTO($course->price, $course->currency) : null;
    }

    public function getPublishedStructure(int $courseId, int $versionId): ?CourseStructureDTO
    {
        $version = $this->courseVersionRepository->findOneOfCourse($courseId, $versionId);

        return $version !== null ? $this->toStructure($version) : null;
    }

    private function toDTO(Course $course): CourseDirectoryDTO
    {
        return new CourseDirectoryDTO(
            id: $course->id,
            uuid: $course->uuid,
            authorId: $course->user_id,
            categoryId: $course->category_id,
            slug: $course->slug,
            title: $course->title,
            status: $course->status,
            price: $course->price,
            currency: $course->currency,
            publishedVersionId: $course->published_version_id,
            publishedAt: $course->published_at?->toDateTimeImmutable(),
        );
    }

    private function toStructure(CourseVersion $version): CourseStructureDTO
    {
        $snapshot = $version->snapshot;
        $sections = is_array($snapshot['sections'] ?? null) ? $snapshot['sections'] : [];

        return new CourseStructureDTO(
            courseId: $version->course_id,
            versionId: $version->id,
            version: $version->version,
            publishedAt: $version->published_at->toDateTimeImmutable(),
            title: is_string($snapshot['title'] ?? null) ? $snapshot['title'] : '',
            sections: array_values(array_map(
                static fn (array $section): CourseStructureSectionDTO => CourseStructureSectionDTO::fromSnapshot($section),
                array_filter($sections, is_array(...)),
            )),
        );
    }
}
