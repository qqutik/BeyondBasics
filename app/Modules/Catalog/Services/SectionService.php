<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\DTO\Section\SectionStoreDTO;
use App\Modules\Catalog\DTO\Section\SectionUpdateDTO;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\Section;
use App\Modules\Catalog\Repositories\SectionRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final readonly class SectionService
{
    public function __construct(
        private SectionRepository $repository,
        private CourseDraftService $draftService,
    ) {}

    /**
     * Every change below puts the course back to draft: it has to be moderated again.
     */
    public function store(SectionStoreDTO $DTO, Course $course): Section
    {
        return DB::transaction(function () use ($DTO, $course): Section {
            $this->draftService->markAsDraft($course->id);

            return $this->repository->store($DTO, $course);
        });
    }

    public function update(SectionUpdateDTO $DTO, Section $section): Section
    {
        return DB::transaction(function () use ($DTO, $section): Section {
            $this->draftService->markAsDraft($section->course_id);

            return $this->repository->update($DTO, $section);
        });
    }

    public function destroy(Section $section): Section
    {
        return DB::transaction(function () use ($section): Section {
            $this->draftService->markAsDraft($section->course_id);

            return $this->repository->destroy($section);
        });
    }

    /**
     * @return list<int>
     */
    public function idsOfCourse(Course $course): array
    {
        return $this->repository->idsOfCourse($course);
    }

    /**
     * @param  list<int>  $ids  all section ids of the course in the new order
     */
    public function updatePositions(Course $course, array $ids): void
    {
        $this->repository->updatePositions($course, $ids);
    }

    /**
     * @return Collection<int, Section>
     */
    public function findAllByCourseWithLessons(Course $course): Collection
    {
        return $this->repository->findAllByCourseWithLessons($course);
    }
}
