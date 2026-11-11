<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\DTO\Section\SectionStoreDTO;
use App\Modules\Catalog\DTO\Section\SectionUpdateDTO;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\Section;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class SectionRepository
{
    /**
     * course_id is taken from the route model, never from the DTO, so a section cannot be attached to another course.
     */
    public function store(SectionStoreDTO $DTO, Course $course): Section
    {
        $section = new Section;
        $section->fill($DTO->toArray());
        $section->course_id = $course->id;
        $section->save();

        return $section;
    }

    public function update(SectionUpdateDTO $DTO, Section $section): Section
    {
        $section->update($DTO->toArray());

        return $section;
    }

    public function destroy(Section $section): Section
    {
        $section->delete();

        return $section;
    }

    /**
     * @return Collection<int, Section>
     */
    public function findAllByCourse(Course $course): Collection
    {
        return Section::query()
            ->where('course_id', $course->id)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return list<int>
     */
    public function idsOfCourse(Course $course): array
    {
        return array_values(
            Section::query()
                ->where('course_id', $course->id)
                ->pluck('id')
                ->map(static fn (mixed $id): int => is_numeric($id) ? (int) $id : 0)
                ->all(),
        );
    }

    /**
     * One UPDATE for the whole course: position = index of the id in the array (1-based).
     * The caller guarantees $ids is exactly the set of this course's sections.
     *
     * @param  list<int>  $ids
     */
    public function updatePositions(Course $course, array $ids): void
    {
        DB::update(
            'UPDATE sections SET position = array_position(?::bigint[], id), updated_at = now() WHERE course_id = ?',
            ['{'.implode(',', $ids).'}', $course->id],
        );
    }
}
