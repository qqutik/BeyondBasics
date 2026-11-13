<?php

declare(strict_types=1);

use App\Modules\Catalog\Enums\Course\CourseLevelEnum;
use App\Modules\Catalog\Enums\LanguageEnum;
use App\Modules\Catalog\Enums\Lesson\LessonTypeEnum;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\CourseVersion;
use App\Modules\Catalog\Models\Lesson;
use App\Modules\Catalog\Models\Section;
use App\Modules\Catalog\Services\CourseService;
use App\Modules\Identity\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * A course that passes every publish rule: filled header, one section with a video and a text lesson.
 *
 * @return array{course: Course, section: Section, video: Lesson, text: Lesson}
 */
function publishableCourse(): array
{
    $category = Category::query()->create(['name' => 'PHP', 'slug' => 'php-pub', 'position' => 1, 'is_active' => true]);

    $course = Course::query()->create([
        'user_id' => User::factory()->create()->id,
        'category_id' => $category->id,
        'slug' => 'publishable',
        'title' => 'Publishable',
        'description' => 'Full description',
        'level' => CourseLevelEnum::BEGINNER,
        'language' => LanguageEnum::EN,
    ]);

    $section = Section::query()->create(['course_id' => $course->id, 'title' => 'Intro', 'position' => 1]);

    return [
        'course' => $course,
        'section' => $section,
        'video' => publishLesson($section, LessonTypeEnum::VIDEO, 'Welcome', ['media_id' => 55]),
        'text' => publishLesson($section, LessonTypeEnum::TEXT, 'Setup', ['body' => '## Setup']),
    ];
}

/**
 * @param  array<string, mixed>  $attributes
 */
function publishLesson(Section $section, LessonTypeEnum $type, string $title, array $attributes = []): Lesson
{
    $lesson = new Lesson(['type' => $type, 'title' => $title, ...$attributes]);
    $lesson->section_id = $section->id;
    $lesson->position = Lesson::query()->where('section_id', $section->id)->count() + 1;
    $lesson->save();

    return $lesson;
}

/**
 * @return array<array-key, mixed> validation errors, [] when the course was published
 */
function publishErrors(Course $course): array
{
    try {
        app(CourseService::class)->generateSnapshot($course->refresh());

        return [];
    } catch (ValidationException $e) {
        return $e->errors();
    }
}

it('creates a version for a complete course', function () {
    ['course' => $course] = publishableCourse();

    expect(publishErrors($course))->toBe([])
        ->and(CourseVersion::query()->where('course_id', $course->id)->count())->toBe(1);
});

it('requires the course header fields', function () {
    ['course' => $course] = publishableCourse();
    $course->update(['description' => '  ', 'category_id' => null, 'level' => null, 'language' => null]);

    expect(array_keys(publishErrors($course)))->toEqualCanonicalizing(['description', 'category_id', 'level', 'language']);
});

it('requires at least one section', function () {
    ['course' => $course, 'section' => $section] = publishableCourse();
    $section->delete();

    expect(publishErrors($course))->toHaveKey('sections');
});

it('rejects an empty section', function () {
    ['course' => $course] = publishableCourse();
    $empty = Section::query()->create(['course_id' => $course->id, 'title' => 'Empty', 'position' => 2]);

    expect(publishErrors($course))->toHaveKey("sections.{$empty->id}");
});

it('requires a video for video lessons and a text for text lessons', function () {
    ['course' => $course, 'video' => $video, 'text' => $text] = publishableCourse();
    $video->update(['media_id' => null]);
    $text->update(['body' => '']);

    $errors = publishErrors($course);

    expect($errors)->toHaveKeys(["lessons.{$video->id}", "lessons.{$text->id}"])
        ->and(json_encode($errors["lessons.{$video->id}"]))->toContain('Welcome');
});

it('reports every problem at once and creates no version', function () {
    ['course' => $course, 'section' => $section, 'video' => $video] = publishableCourse();
    $course->update(['description' => null]);
    $video->update(['media_id' => null]);
    $empty = Section::query()->create(['course_id' => $course->id, 'title' => 'Empty', 'position' => 2]);

    expect(array_keys(publishErrors($course)))->toEqualCanonicalizing(['description', "sections.{$empty->id}", "lessons.{$video->id}"])
        ->and(CourseVersion::query()->where('course_id', $course->id)->count())->toBe(0);
});

it('does not check quiz lessons yet', function () {
    ['course' => $course, 'section' => $section] = publishableCourse();
    publishLesson($section, LessonTypeEnum::QUIZ, 'Quiz');

    expect(publishErrors($course))->toBe([]);
});
