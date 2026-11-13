<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Requests\Course\CourseRejectRequest;
use App\Modules\Catalog\Http\Resources\CourseResource;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Services\CourseStatusService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Who may do it: CoursePolicy. Whether the transition is allowed from the current status:
 * CourseStatusService (CourseStatusEnum::allowedTransitions(), checked on the locked row, 422 otherwise).
 */
final readonly class CourseStatusController
{
    public function __construct(private CourseStatusService $service) {}

    public function submit(Course $course): CourseResource
    {
        Gate::authorize('submit', $course);

        return new CourseResource($this->service->submit($course));
    }

    public function archive(Course $course): CourseResource
    {
        Gate::authorize('archive', $course);

        return new CourseResource($this->service->archive($course));
    }

    public function approve(Course $course): CourseResource
    {
        Gate::authorize('approve', $course);

        return new CourseResource($this->service->approve($course, $this->currentUserId()));
    }

    public function reject(CourseRejectRequest $request, Course $course): CourseResource
    {
        Gate::authorize('reject', $course);

        return new CourseResource($this->service->reject($course, $this->currentUserId(), $request->reason()));
    }

    public function restore(Course $course): CourseResource
    {
        Gate::authorize('restore', $course);

        return new CourseResource($this->service->restore($course));
    }

    private function currentUserId(): int
    {
        $id = Auth::id();

        return is_numeric($id) ? (int) $id : 0;
    }
}
