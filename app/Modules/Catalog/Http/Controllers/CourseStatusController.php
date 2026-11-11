<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Enums\Course\CourseStatusEnum;
use App\Modules\Catalog\Http\Resources\CourseResource;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Services\CourseStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

final readonly class CourseStatusController
{
    public function __construct(private CourseStatusService $service) {}

    public function submit(Course $course)
    {
        Gate::authorize('submit', $course);

        if ($course->status !== CourseStatusEnum::DRAFT){
            return throw new \Exception('Course must be in draft status');
        }

        $course = $this->service->submit($course);
        return new CourseResource($course);
    }

    public function archive(Course $course)
    {
        Gate::authorize('archive', $course);

        if ($course->status === CourseStatusEnum::ARCHIVED){
            return throw new \Exception('Course is already archived');
        }

        $course =  $this->service->archive($course);
        return new CourseResource($course);
    }

    public function approve(Course $course)
    {
        Gate::authorize('approve', $course);

        if ($course->status !== CourseStatusEnum::REVIEW){
            return throw new \Exception('Course must be in review status');
        }

        $course = $this->service->approve($course, Auth::user()->id);
        return new CourseResource($course);
    }

    public function reject(Request $request, Course $course)
    {
        Gate::authorize('reject', $course);

        if ($course->status !== CourseStatusEnum::REVIEW){
            return throw new \Exception('Course must be in review status');
        }

        $course = $this->service->reject($course, Auth::user()->id, $request->reason);
        return new CourseResource($course);
    }

    public function restore(Course $course)
    {
        Gate::authorize('restore', $course);

        if ($course->status !== CourseStatusEnum::ARCHIVED){
            return throw new \Exception('Course must be in archive status');
        }

        $course =  $this->service->restore($course);
        return new CourseResource($course);
    }

}
