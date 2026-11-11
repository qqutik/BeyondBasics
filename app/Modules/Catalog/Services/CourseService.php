<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\DTO\Course\CourseFilterDTO;
use App\Modules\Catalog\DTO\Course\CourseStoreDTO;
use App\Modules\Catalog\DTO\Course\CourseUpdateDTO;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\CourseVersion;
use App\Modules\Catalog\Repositories\CourseRepository;
use App\Modules\Catalog\Traits\SlugTrait;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

final readonly class CourseService
{
    use SlugTrait;

    public function __construct(private CourseRepository $repository) {}

    public function findAll(CourseFilterDTO $DTO): LengthAwarePaginator
    {
        return $this->repository->findAll($DTO);
    }

    public function findOneById(int $id): ?Course
    {
        return $this->repository->findOneById($id);
    }

    public function store(CourseStoreDTO $DTO): Course
    {
        $slug = $this->generateSlug($DTO->title);

        if ($slug === '') {
            throw ValidationException::withMessages(['name' => 'The title must contain at least one letter or digit.']);
        }

        $DTO->setSlug($slug);

        return $this->repository->store($DTO);
    }

    public function update(CourseUpdateDTO $DTO, Course $course): Course
    {
        if ($DTO->isEmpty()) {
            return $course;
        }

        return $this->repository->update($DTO, $course);
    }

    public function destroy(Course $course): Course
    {
        return $this->repository->destroy($course);
    }

    public function generateSnapshot(Course $course): CourseVersion
    {
        $courseVersion = new CourseVersion();
        $courseVersion->course_id = $course->id;
        $courseVersion->snapshot = $course->toArray();
        $courseVersion->published_at = now();
        $courseVersion->version = $course->published_version_id + 1;
        $courseVersion->save();

        return $courseVersion;
    }
}
