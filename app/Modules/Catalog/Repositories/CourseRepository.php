<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\DTO\Course\CourseFilterDTO;
use App\Modules\Catalog\DTO\Course\CourseStoreDTO;
use App\Modules\Catalog\DTO\Course\CourseUpdateDTO;
use App\Modules\Catalog\Filters\Course\CourseFilters;
use App\Modules\Catalog\Models\Course;
use Illuminate\Database\Eloquent\Collection;
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

    /**
     * Public catalog: only courses on sale (scope published), with the same filters as the editor list.
     *
     * @return LengthAwarePaginator<int, Course>
     */
    public function findPublished(CourseFilterDTO $filter): LengthAwarePaginator
    {
        return $this->filters
            ->apply(Course::query()->published(), $filter)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($filter->perPage)
            ->withQueryString();
    }

    /**
     * A course on sale by slug, with its current sections and lessons (both relations are ordered by position).
     */
    public function findPublishedBySlug(string $slug): ?Course
    {
        return Course::query()
            ->published()
            ->where('slug', $slug)
            ->with('sections.lessons')
            ->first();
    }

    public function findOneById(int $id): ?Course
    {
        return Course::query()->whereKey($id)->first();
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, Course>
     */
    public function findManyByIds(array $ids): Collection
    {
        return Course::query()->whereKey($ids)->get();
    }

    public function slugExists(string $slug): bool
    {
        return Course::withTrashed()->where('slug', $slug)->exists();
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

    /**
     * Locks the course row until the end of the transaction.
     */
    public function lockForUpdate(Course $course): void
    {
        Course::query()->whereKey($course->id)->lockForUpdate()->firstOrFail();
    }

    public function destroy(Course $course): Course
    {
        $course->delete();

        return $course;
    }
}
