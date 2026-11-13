<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Enums\Course\CourseStatusEnum;
use App\Modules\Catalog\Repositories\CourseRepository;
use Illuminate\Validation\ValidationException;

final readonly class CourseDraftService
{
    public function __construct(private CourseRepository $repository) {}

    public function markAsDraft(int $courseId): void
    {
        $status = $this->repository->lockStatus($courseId);

        if ($status === CourseStatusEnum::ARCHIVED) {
            throw ValidationException::withMessages([
                'status' => 'An archived course cannot be edited. Restore it first.',
            ]);
        }

        if ($status !== CourseStatusEnum::DRAFT) {
            $this->repository->setStatus($courseId, CourseStatusEnum::DRAFT);
        }
    }
}
