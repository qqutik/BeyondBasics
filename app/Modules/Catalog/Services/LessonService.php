<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\DTO\Lesson\LessonStoreDTO;
use App\Modules\Catalog\DTO\Lesson\LessonUpdateDTO;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\Lesson;
use App\Modules\Catalog\Models\Section;
use App\Modules\Catalog\Repositories\LessonRepository;

final readonly class LessonService
{
    public function __construct(private LessonRepository $repository) {}

    public function store(LessonStoreDTO $DTO, Section $section): Lesson
    {
        return $this->repository->store($DTO, $section);
    }

    public function update(LessonUpdateDTO $DTO, Lesson $lesson): Lesson
    {
        return $this->repository->update($DTO, $lesson);
    }

    public function destroy(Lesson $lesson): Lesson
    {
        return $this->repository->destroy($lesson);
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
