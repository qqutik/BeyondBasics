<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\DTO\Section\SectionStoreDTO;
use App\Modules\Catalog\DTO\Section\SectionUpdateDTO;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\Section;
use App\Modules\Catalog\Repositories\SectionRepository;
use Illuminate\Database\Eloquent\Collection;

final readonly class SectionService
{
    public function __construct(private SectionRepository $repository) {}

    public function store(SectionStoreDTO $DTO, Course $course): Section
    {
        return $this->repository->store($DTO, $course);
    }

    public function update(SectionUpdateDTO $DTO, Section $section): Section
    {
        return $this->repository->update($DTO, $section);
    }

    public function destroy(Section $section): Section
    {
        return $this->repository->destroy($section);
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
