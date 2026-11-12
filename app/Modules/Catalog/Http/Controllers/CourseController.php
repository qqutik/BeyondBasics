<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Requests\Course\CourseIndexRequest;
use App\Modules\Catalog\Http\Requests\Course\CourseStoreRequest;
use App\Modules\Catalog\Http\Requests\Course\CourseUpdateRequest;
use App\Modules\Catalog\Http\Resources\CourseResource;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Services\CourseService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class CourseController
{
    public function __construct(private readonly CourseService $service) {}

    public function index(CourseIndexRequest $request): AnonymousResourceCollection
    {
        $courses = $this->service->findAll($request->toDTO());

        return CourseResource::collection($courses);
    }

    public function show(Course $course): CourseResource
    {
        $course = $this->service->findOneById($course->id);

        return new CourseResource($course);
    }

    public function store(CourseStoreRequest $request): CourseResource
    {
        $category = $this->service->store($request->toDTO());

        return new CourseResource($category);
    }

    public function update(CourseUpdateRequest $request, Course $course): CourseResource
    {
        $course = $this->service->update($request->toDTO(), $course);

        return new CourseResource($course);
    }

    public function destroy(Course $course): CourseResource
    {
        $course = $this->service->destroy($course);

        return new CourseResource($course);
    }
}
