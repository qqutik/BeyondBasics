<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\Models\CourseVersion;

final class CourseVersionRepository
{
    /**
     * A version only if it belongs to the given course: a foreign version id gives null.
     */
    public function findOneOfCourse(int $courseId, int $versionId): ?CourseVersion
    {
        return CourseVersion::query()
            ->where('course_id', $courseId)
            ->whereKey($versionId)
            ->first();
    }
}
