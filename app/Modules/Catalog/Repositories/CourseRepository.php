<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\DTO\Course\CourseFilterDTO;
use App\Modules\Catalog\DTO\Course\CourseStoreDTO;
use App\Modules\Catalog\DTO\Course\CourseUpdateDTO;
use App\Modules\Catalog\Filters\Course\CourseFilters;
use App\Modules\Catalog\Models\Course;
use Illuminate\Pagination\LengthAwarePaginator;

final class CourseRepository
{
    public function __construct(
        private readonly CourseFilters $filters,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Course>
     */
    public function findAll(CourseFilterDTO $filter): LengthAwarePaginator
    {
        return $this->filters
            ->apply(Course::query(), $filter)
            ->orderBy('published_at')
            ->paginate($filter->perPage)
            ->withQueryString();
    }

    public function findOneById(int $id): ?Course
    {
        return Course::where('id', $id)->first();
    }

    public function store(CourseStoreDTO $DTO): Course
    {
        return Course::create($DTO->toArray());
    }

    public function update(CourseUpdateDTO $DTO, Course $course): Course
    {
        $course->update($DTO->toArray());

        return $course;
    }

    public function destroy(Course $course): Course
    {
        $course->delete();

        return $course;
    }
}
