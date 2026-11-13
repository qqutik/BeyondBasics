<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\DTO\Course\CourseFilterDTO;
use App\Modules\Catalog\DTO\Course\CourseStoreDTO;
use App\Modules\Catalog\DTO\Course\CourseUpdateDTO;
use App\Modules\Catalog\Enums\Lesson\LessonTypeEnum;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\CourseVersion;
use App\Modules\Catalog\Models\Lesson;
use App\Modules\Catalog\Models\Section;
use App\Modules\Catalog\Repositories\CourseRepository;
use App\Modules\Catalog\Repositories\CourseVersionRepository;
use App\Modules\Catalog\Traits\SlugTrait;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CourseService
{
    use SlugTrait;

    public function __construct(
        private CourseRepository $repository,
        private CourseVersionRepository $courseVersionRepository,
        private SectionService $sectionService,
        private CourseDraftService $draftService,
    ) {}

    public function findAll(CourseFilterDTO $DTO): LengthAwarePaginator
    {
        return $this->repository->findAll($DTO);
    }

    public function findOneById(int $id): ?Course
    {
        return $this->repository->findOneById($id);
    }

    public function store(CourseStoreDTO $DTO): Course
    {
        $slug = $this->generateSlug($DTO->title);

        if ($slug === '') {
            throw ValidationException::withMessages(['name' => 'The title must contain at least one letter or digit.']);
        }

        $DTO->setSlug($slug);

        return $this->repository->store($DTO);
    }

    public function update(CourseUpdateDTO $DTO, Course $course): Course
    {
        if ($DTO->isEmpty()) {
            return $course;
        }

        return DB::transaction(function () use ($DTO, $course): Course {
            $this->draftService->markAsDraft($course->id);

            return $this->repository->update($DTO, $course)->refresh();
        });
    }

    public function destroy(Course $course): Course
    {
        return $this->repository->destroy($course);
    }

    public function generateSnapshot(Course $course): CourseVersion
    {
        $this->assertPublishable($course);

        return $this->courseVersionRepository->create($course, $this->buildSnapshot($course));
    }

    private function buildSnapshot(Course $course): array
    {
        $sections = $this->sectionService->findAllByCourseWithLessons($course);

        return [
            'title' => $course->title,
            'subtitle' => $course->subtitle,
            'description' => $course->description,
            'level' => $course->level?->value,
            'language' => $course->language?->value,
            'sections' => $sections->map(static fn (Section $section): array => [
                'id' => $section->id,
                'position' => $section->position,
                'title' => $section->title,
                'lessons' => $section->lessons->map(static fn (Lesson $lesson): array => [
                    'id' => $lesson->id,
                    'position' => $lesson->position,
                    'type' => $lesson->type->value,
                    'title' => $lesson->title,
                    'body' => $lesson->body,
                    'media_id' => $lesson->media_id,
                    'duration_sec' => $lesson->duration_sec,
                    'is_preview' => $lesson->is_preview,
                    'unlock_after_days' => $lesson->unlock_after_days,
                ])->all(),
            ])->all(),
        ];
    }

    /**
     * A course can be published only when a student would get something complete and usable.
     * Collects every problem at once (not the first one), so the author fixes everything in one go:
     * 422 with keys like "description", "sections", "sections.3", "lessons.12".
     *
     * Quiz lessons are not checked yet: questions will live in their own tables.
     * Called on submit (the author learns about problems before moderation) and again on publish
     * (the course could have been edited while in review).
     *
     * @throws ValidationException
     */
    public function assertPublishable(Course $course): void
    {
        $errors = [];

        if (trim($course->title) === '') {
            $errors['title'] = 'The course must have a title.';
        }

        if ($course->description === null || trim($course->description) === '') {
            $errors['description'] = 'The course must have a description.';
        }

        if ($course->category_id === null) {
            $errors['category_id'] = 'The course must be in a category.';
        }

        if ($course->level === null) {
            $errors['level'] = 'The course must have a level.';
        }

        if ($course->language === null) {
            $errors['language'] = 'The course must have a language.';
        }

        $sections = $this->sectionService->findAllByCourseWithLessons($course);

        if ($sections->isEmpty()) {
            $errors['sections'] = 'The course must have at least one section with a lesson.';
        }

        foreach ($sections as $section) {
            if ($section->lessons->isEmpty()) {
                $errors["sections.{$section->id}"] = "Section \"{$section->title}\" has no lessons.";
            }

            foreach ($section->lessons as $lesson) {
                $problem = $this->lessonProblem($lesson);

                if ($problem !== null) {
                    $errors["lessons.{$lesson->id}"] = $problem;
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * What is missing for this lesson to be shown to a student, or null when it is ready.
     */
    private function lessonProblem(Lesson $lesson): ?string
    {
        return match ($lesson->type) {
            LessonTypeEnum::VIDEO => $lesson->media_id === null
                ? "Lesson \"{$lesson->title}\" has no video uploaded."
                : null,
            LessonTypeEnum::TEXT => $lesson->body === null || trim($lesson->body) === ''
                ? "Lesson \"{$lesson->title}\" has no text."
                : null,
            LessonTypeEnum::QUIZ => null,
        };
    }
}
