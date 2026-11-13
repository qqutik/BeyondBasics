<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\DTO\Lesson\LessonStoreDTO;
use App\Modules\Catalog\DTO\Lesson\LessonUpdateDTO;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\Lesson;
use App\Modules\Catalog\Models\Section;
use App\Modules\Catalog\Repositories\LessonRepository;
use Illuminate\Support\Facades\DB;

final readonly class LessonService
{
    public function __construct(
        private LessonRepository $repository,
        private CourseDraftService $draftService,
    ) {}

    /**
     * Every change below puts the course back to draft: it has to be moderated again.
     */
    public function store(LessonStoreDTO $DTO, Section $section): Lesson
    {
        return DB::transaction(function () use ($DTO, $section): Lesson {
            $this->draftService->markAsDraft($section->course_id);

            return $this->repository->store($DTO, $section);
        });
    }

    public function update(LessonUpdateDTO $DTO, Lesson $lesson): Lesson
    {
        return DB::transaction(function () use ($DTO, $lesson): Lesson {
            $this->draftService->markAsDraft($lesson->section->course_id);

            return $this->repository->update($DTO, $lesson);
        });
    }

    public function destroy(Lesson $lesson): Lesson
    {
        return DB::transaction(function () use ($lesson): Lesson {
            $this->draftService->markAsDraft($lesson->section->course_id);

            return $this->repository->destroy($lesson);
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
     * @param  list<int>  $ids  lesson ids in their new order inside the section
     */
    public function placeInSection(int $sectionId, array $ids): void
    {
        $this->repository->placeInSection($sectionId, $ids);
    }
}
