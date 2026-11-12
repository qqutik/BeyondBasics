<?php

declare(strict_types=1);

use App\Modules\Catalog\Contracts\CourseDirectoryContract;
use App\Modules\Catalog\Enums\Course\CourseStatusEnum;
use App\Modules\Catalog\Enums\CurrencyEnum;
use App\Modules\Catalog\Enums\Lesson\LessonTypeEnum;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\CourseVersion;
use App\Modules\Identity\Models\User;

function directory(): CourseDirectoryContract
{
    return app(CourseDirectoryContract::class);
}

/**
 * A published course with one version whose snapshot has 2 sections / 3 lessons.
 *
 * @return array{course: Course, version: CourseVersion}
 */
function publishedCourseFixture(string $slug = 'directory-course'): array
{
    $course = Course::query()->create([
        'user_id' => User::factory()->create()->id, 'slug' => $slug, 'title' => 'Directory course',
        'price' => 2999, 'currency' => CurrencyEnum::EUR,
    ]);

    $version = new CourseVersion([
        'course_id' => $course->id,
        'published_at' => now(),
        'snapshot' => [
            'title' => 'Directory course v1',
            'sections' => [
                ['id' => 1, 'position' => 1, 'title' => 'Intro', 'lessons' => [
                    ['id' => 10, 'position' => 1, 'type' => 'video', 'title' => 'Welcome', 'body' => null, 'media_id' => null, 'duration_sec' => 120, 'is_preview' => true, 'unlock_after_days' => null],
                    ['id' => 11, 'position' => 2, 'type' => 'text', 'title' => 'Setup', 'body' => '## Setup', 'media_id' => null, 'duration_sec' => 300, 'is_preview' => false, 'unlock_after_days' => 7],
                ]],
                ['id' => 2, 'position' => 2, 'title' => 'Main', 'lessons' => [
                    ['id' => 12, 'position' => 1, 'type' => 'quiz', 'title' => 'Quiz', 'body' => null, 'media_id' => null, 'duration_sec' => 60, 'is_preview' => false, 'unlock_after_days' => null],
                ]],
            ],
        ],
    ]);
    $version->version = 1;
    $version->save();

    $course->status = CourseStatusEnum::PUBLISHED;
    $course->published_version_id = $version->id;
    $course->published_at = now();
    $course->save();

    return ['course' => $course, 'version' => $version];
}

it('returns a course as a DTO', function () {
    ['course' => $course, 'version' => $version] = publishedCourseFixture();

    $dto = directory()->findOneById($course->id);

    expect($dto)->not->toBeNull()
        ->and($dto?->id)->toBe($course->id)
        ->and($dto?->uuid)->toBe($course->uuid)
        ->and($dto?->authorId)->toBe($course->user_id)
        ->and($dto?->status)->toBe(CourseStatusEnum::PUBLISHED)
        ->and($dto?->publishedVersionId)->toBe($version->id)
        ->and($dto?->isPublished())->toBeTrue()
        ->and($dto?->isFree())->toBeFalse();
});

it('returns null for a missing or deleted course', function () {
    ['course' => $course] = publishedCourseFixture();
    $course->delete();

    expect(directory()->findOneById($course->id))->toBeNull()
        ->and(directory()->findOneById(999999))->toBeNull();
});

it('finds many courses keyed by id and skips missing ones', function () {
    ['course' => $first] = publishedCourseFixture('first');
    ['course' => $second] = publishedCourseFixture('second');

    $found = directory()->findMany([$second->id, $first->id, $first->id, 999999]);

    expect(array_keys($found))->toEqualCanonicalizing([$first->id, $second->id])
        ->and($found[$first->id]->slug)->toBe('first')
        ->and(directory()->findMany([]))->toBe([]);
});

it('returns the price in minor units with the currency', function () {
    ['course' => $course] = publishedCourseFixture();

    $price = directory()->getPrice($course->id);

    expect($price?->amount)->toBe(2999)
        ->and($price?->currency)->toBe(CurrencyEnum::EUR)
        ->and($price?->formatted())->toBe('€29.99')
        ->and(directory()->getPrice(999999))->toBeNull();
});

it('returns the published structure from the snapshot', function () {
    ['course' => $course, 'version' => $version] = publishedCourseFixture();

    $structure = directory()->getPublishedStructure($course->id, $version->id);

    expect($structure?->version)->toBe(1)
        ->and($structure?->title)->toBe('Directory course v1')
        ->and($structure?->sections)->toHaveCount(2)
        ->and($structure?->sections[0]->lessons)->toHaveCount(2)
        ->and($structure?->totalDurationSec())->toBe(480);

    $setup = $structure?->findLesson(11);

    expect($setup?->type)->toBe(LessonTypeEnum::TEXT)
        ->and($setup?->isUnlockedAfter(6))->toBeFalse()
        ->and($setup?->isUnlockedAfter(7))->toBeTrue()
        ->and($structure?->findLesson(10)?->isPreview)->toBeTrue();
});

it('keeps the structure available after the course is deleted', function () {
    ['course' => $course, 'version' => $version] = publishedCourseFixture();
    $course->delete();

    expect(directory()->getPublishedStructure($course->id, $version->id))->not->toBeNull();
});

it('does not return a version of another course', function () {
    ['version' => $version] = publishedCourseFixture('owner');
    ['course' => $other] = publishedCourseFixture('other');

    expect(directory()->getPublishedStructure($other->id, $version->id))->toBeNull();
});
