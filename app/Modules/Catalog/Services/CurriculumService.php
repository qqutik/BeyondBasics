<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\DTO\Course\CurriculumDTO;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\Section;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Order of the course structure: sections and lessons, including moving lessons between sections.
 * One endpoint, one transaction: the client sends the final state, the server checks it and saves it.
 */
final readonly class CurriculumService
{
    public function __construct(
        private CourseDraftService $draft,
        private SectionService $sections,
        private LessonService $lessons,
    ) {}

    /**
     * The request must list exactly all sections and all lessons of the course, each once.
     * A stale structure (another tab added a lesson), a foreign id or a missing one gives 422
     * instead of silently breaking the order.
     *
     * @return Collection<int, Section> sections in the new order, with their lessons
     */
    public function update(Course $course, CurriculumDTO $DTO): Collection
    {
        return DB::transaction(function () use ($course, $DTO): Collection {
            // locks the course (serializes all structure changes) and puts it back to draft:
            // a new order is a change students will see, so it is moderated like any other edit
            $this->draft->markAsDraft($course->id);

            if (! $this->sameIds($this->sections->idsOfCourse($course), $DTO->sectionIds())) {
                throw ValidationException::withMessages([
                    'sections' => 'The sections must contain every section of this course exactly once.',
                ]);
            }

            if (! $this->sameIds($this->lessons->idsOfCourse($course), $DTO->lessonIds())) {
                throw ValidationException::withMessages([
                    'sections' => 'The lessons must contain every lesson of this course exactly once.',
                ]);
            }

            $this->sections->updatePositions($course, $DTO->sectionIds());

            foreach ($DTO->sections as $section) {
                $this->lessons->placeInSection($section->id, $section->lessons);
            }

            return $this->sections->findAllByCourseWithLessons($course);
        });
    }

    /**
     * @param  list<int>  $current
     * @param  list<int>  $sent
     */
    private function sameIds(array $current, array $sent): bool
    {
        sort($current);
        sort($sent);

        return $current === $sent;
    }
}
