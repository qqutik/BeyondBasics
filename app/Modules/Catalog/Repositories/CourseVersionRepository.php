<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\Models\Course;
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

    private function getNextVersionNumber(Course $course): int
    {
        $max = $course->versions()->max('version');

        return (is_numeric($max) ? (int) $max : 0) + 1;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function create(Course $course, array $snapshot): CourseVersion
    {
        $version = new CourseVersion;
        $version->course_id = $course->id;
        $version->version = $this->getNextVersionNumber($course);
        $version->snapshot = $snapshot;
        $version->published_at = now();
        $version->save();

        return $version;
    }
}
