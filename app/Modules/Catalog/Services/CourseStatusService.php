<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\DTO\Audit\AuditDTO;
use App\Modules\Catalog\Enums\Course\CourseStatusEnum;
use App\Modules\Catalog\Events\CourseDeclinedEvent;
use App\Modules\Catalog\Events\CoursePublishedEvent;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Services\Audit\AuditContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CourseStatusService
{
    public function __construct(private CourseService $courseService, private AuditContract $auditContract) {}

    /**
     * 422 with the list of problems when the course is not complete, see CourseService::assertPublishable().
     */
    public function submit(Course $course): ?Course
    {
        return $this->transition($course, CourseStatusEnum::REVIEW);
    }

    public function archive(Course $course): ?Course
    {
        return $this->transition($course, CourseStatusEnum::ARCHIVED);
    }

    public function approve(Course $course, int $adminId): ?Course
    {
        $result = $this->transition($course, CourseStatusEnum::PUBLISHED, CoursePublishedEvent::class);
        if ($result) {
            $this->generateLog($adminId, 'Course approved', $course);
        }

        return $result;
    }

    public function reject(Course $course, int $adminId, string $reason): ?Course
    {
        $result = $this->transition($course, CourseStatusEnum::DECLINED, CourseDeclinedEvent::class);
        if ($result) {
            $this->generateLog($adminId, 'Course rejected: '.$reason, $course);
        }

        return $result;
    }

    public function restore(Course $course): ?Course
    {
        return $this->transition($course, CourseStatusEnum::DRAFT);
    }

    private function generateLog(int $adminId, string $message, Course $course): void
    {
        try {
            $this->auditContract->log(new AuditDTO(
                user_id: $adminId,
                message: $message,
                instance_id: $course->id,
                instance_type: Course::class,
                ip: null,
                additional: null,
                channel: null,
            ));
        } catch (\Throwable $exception) {

        }
    }

    /**
     * @throws \Throwable
     */
    private function transition(Course $course, CourseStatusEnum $status, $event = null): ?Course
    {
        return DB::transaction(function () use ($course, $status, $event) {

            $course = Course::query()->lockForUpdate()->findOrFail($course->id);

            if (! $course->status->canTransitionTo($status)) {
                throw ValidationException::withMessages([
                    'status' => "Cannot change status from {$course->status->value} to {$status->value}.",
                ]);
            }

            // checked on the locked row, inside the transaction: nothing can change the course in between
            if ($status === CourseStatusEnum::REVIEW) {
                $this->courseService->assertPublishable($course);
            }

            if ($status === CourseStatusEnum::PUBLISHED) {
                $version = $this->courseService->generateSnapshot($course);
                $course->published_version_id = $version->id;
                $course->published_at = $version->published_at;
            }

            $course->status = $status;
            $course->save();

            if ($event !== null) {
                event(new $event($course->id));
            }

            return $course;
        });
    }
}
