<?php

declare(strict_types=1);

use App\Modules\Catalog\Enums\Course\CourseLevelEnum;
use App\Modules\Catalog\Enums\Course\CourseStatusEnum;
use App\Modules\Catalog\Enums\LanguageEnum;
use App\Modules\Catalog\Enums\Lesson\LessonTypeEnum;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\Lesson;
use App\Modules\Catalog\Models\Section;
use App\Modules\Identity\Models\User;

use function Pest\Laravel\postJson;

/**
 * A complete draft course with one section and one text lesson, owned by a logged-in author.
 *
 * @return array{headers: array{Authorization: string}, course: Course, lesson: Lesson}
 */
function submittableCourse(): array
{
    $author = User::factory()->create();
    $category = Category::query()->create(['name' => 'PHP', 'slug' => 'php-submit', 'position' => 1, 'is_active' => true]);

    $course = Course::query()->create([
        'user_id' => $author->id,
        'category_id' => $category->id,
        'slug' => 'submittable',
        'title' => 'Submittable',
        'description' => 'Full description',
        'level' => CourseLevelEnum::BEGINNER,
        'language' => LanguageEnum::EN,
    ]);

    $section = Section::query()->create(['course_id' => $course->id, 'title' => 'Intro', 'position' => 1]);

    $lesson = new Lesson(['type' => LessonTypeEnum::TEXT, 'title' => 'Setup', 'body' => '## Setup']);
    $lesson->section_id = $section->id;
    $lesson->position = 1;
    $lesson->save();

    return ['headers' => bearer(apiLogin($author->email)['access_token']), 'course' => $course, 'lesson' => $lesson];
}

it('sends a complete course to review', function () {
    ['headers' => $h, 'course' => $course] = submittableCourse();

    postJson("/api/course/{$course->id}/submit", [], $h)->assertOk();

    expect($course->refresh()->status)->toBe(CourseStatusEnum::REVIEW);
});

it('does not send an incomplete course to review and lists the problems', function () {
    ['headers' => $h, 'course' => $course, 'lesson' => $lesson] = submittableCourse();
    $course->update(['description' => null]);
    $lesson->update(['body' => null]);

    postJson("/api/course/{$course->id}/submit", [], $h)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['description', "lessons.{$lesson->id}"]);

    expect($course->refresh()->status)->toBe(CourseStatusEnum::DRAFT);
});
