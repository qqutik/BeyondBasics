<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\DTO\Lesson\LessonStoreDTO;
use App\Modules\Catalog\DTO\Lesson\LessonUpdateDTO;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\Lesson;
use App\Modules\Catalog\Models\Section;
use Illuminate\Support\Facades\DB;

final class LessonRepository
{
    public function store(LessonStoreDTO $DTO, Section $section): Lesson
    {
        $lesson = new Lesson($DTO->toArray());
        $lesson->section_id = $section->id;
        $lesson->position = $section->lessons()->count() + 1;
        $lesson->save();

        return $lesson;
    }

    public function update(LessonUpdateDTO $DTO, Lesson $lesson): Lesson
    {
        $lesson->update($DTO->toArray());

        return $lesson;
    }

    public function destroy(Lesson $lesson): Lesson
    {
        $lesson->delete();

        return $lesson;
    }

    /**
     * All lesson ids of the course (through its sections).
     *
     * @return list<int>
     */
    public function idsOfCourse(Course $course): array
    {
        return array_values(
            Lesson::query()
                ->whereIn('section_id', Section::query()->select('id')->where('course_id', $course->id))
                ->pluck('id')
                ->map(static fn (mixed $id): int => is_numeric($id) ? (int) $id : 0)
                ->all(),
        );
    }

    /**
     * Puts the lessons into the section in the given order: section_id + position = index in the array (1-based).
     * One UPDATE per section; the caller guarantees the ids belong to the same course.
     *
     * @param  list<int>  $ids
     */
    public function placeInSection(int $sectionId, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $array = '{'.implode(',', $ids).'}';

        DB::update(
            'UPDATE lessons SET section_id = ?, position = array_position(?::bigint[], id), updated_at = now() WHERE id = ANY(?::bigint[])',
            [$sectionId, $array, $array],
        );
    }
}
