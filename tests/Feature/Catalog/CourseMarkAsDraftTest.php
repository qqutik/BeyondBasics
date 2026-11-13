<?php

declare(strict_types=1);

use App\Modules\Catalog\Enums\Course\CourseStatusEnum;
use App\Modules\Catalog\Enums\Lesson\LessonTypeEnum;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\Lesson;
use App\Modules\Catalog\Models\Section;
use App\Modules\Catalog\Services\CourseStatusService;
use App\Modules\Identity\Models\User;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

use Symfony\Component\HttpFoundation\Response;

/**
 * A course with one section and one lesson in the given status, owned by a logged-in author.
 *
 * @return array{headers: array{Authorization: string}, course: Course, section: Section, lesson: Lesson}
 */
function draftFixture(CourseStatusEnum $status): array
{
    $author = User::factory()->create();
    $course = Course::query()->create(['user_id' => $author->id, 'slug' => 'draft-course', 'title' => 'Draft course']);

    $section = Section::query()->create(['course_id' => $course->id, 'title' => 'Intro', 'position' => 1]);

    $lesson = new Lesson(['type' => LessonTypeEnum::TEXT, 'title' => 'Setup', 'body' => '## Setup']);
    $lesson->section_id = $section->id;
    $lesson->position = 1;
    $lesson->save();

    $course->status = $status;
    $course->save();

    return ['headers' => bearer(apiLogin($author->email)['access_token']), 'course' => $course, 'section' => $section, 'lesson' => $lesson];
}

/**
 * Every endpoint that changes the content of a course. Called with the fixture spread as named arguments.
 *
 * @return array<string, Closure(array{Authorization: string} $headers, Course $course, Section $section, Lesson $lesson): TestResponse<Response>>
 */
function contentEdits(): array
{
    return [
        'course update' => fn (array $headers, Course $course, Section $section, Lesson $lesson) => putJson("/api/course/{$course->id}", ['title' => 'New title'], $headers),
        'section store' => fn (array $headers, Course $course, Section $section, Lesson $lesson) => postJson("/api/course/{$course->id}/section", ['title' => 'New', 'position' => 2], $headers),
        'section update' => fn (array $headers, Course $course, Section $section, Lesson $lesson) => putJson("/api/course/{$course->id}/section/{$section->id}", ['title' => 'Renamed'], $headers),
        'section destroy' => fn (array $headers, Course $course, Section $section, Lesson $lesson) => deleteJson("/api/course/{$course->id}/section/{$section->id}", [], $headers),
        'lesson store' => fn (array $headers, Course $course, Section $section, Lesson $lesson) => postJson("/api/course/{$course->id}/section/{$section->id}/lesson", ['type' => 'text', 'title' => 'New', 'body' => 'Text'], $headers),
        'lesson update' => fn (array $headers, Course $course, Section $section, Lesson $lesson) => putJson("/api/course/{$course->id}/section/{$section->id}/lesson/{$lesson->id}", ['title' => 'Renamed'], $headers),
        'lesson destroy' => fn (array $headers, Course $course, Section $section, Lesson $lesson) => deleteJson("/api/course/{$course->id}/section/{$section->id}/lesson/{$lesson->id}", [], $headers),
        'curriculum' => fn (array $headers, Course $course, Section $section, Lesson $lesson) => putJson("/api/course/{$course->id}/curriculum", ['sections' => [['id' => $section->id, 'lessons' => [$lesson->id]]]], $headers),
    ];
}

it('puts the course back to draft on every content edit', function (string $edit, CourseStatusEnum $from) {
    $fixture = draftFixture($from);

    $response = contentEdits()[$edit](...$fixture);

    expect($response->status())->toBeLessThan(300)
        ->and($fixture['course']->refresh()->status)->toBe(CourseStatusEnum::DRAFT);
})->with(array_keys(contentEdits()))->with([
    'published' => CourseStatusEnum::PUBLISHED,
    'review' => CourseStatusEnum::REVIEW,
    'declined' => CourseStatusEnum::DECLINED,
    'draft' => CourseStatusEnum::DRAFT,
]);

it('does not let an archived course be edited', function (string $edit) {
    $fixture = draftFixture(CourseStatusEnum::ARCHIVED);

    contentEdits()[$edit](...$fixture)
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('status');

    expect($fixture['course']->refresh()->status)->toBe(CourseStatusEnum::ARCHIVED)
        ->and($fixture['course']->title)->toBe('Draft course')
        ->and(Section::query()->where('course_id', $fixture['course']->id)->count())->toBe(1)
        ->and($fixture['lesson']->refresh()->title)->toBe('Setup');
})->with(array_keys(contentEdits()));

it('keeps the status when the edit is rejected by validation', function () {
    $fixture = draftFixture(CourseStatusEnum::PUBLISHED);

    // stale structure: the lesson is missing, curriculum gives 422 after markAsDraft ran
    putJson("/api/course/{$fixture['course']->id}/curriculum", ['sections' => [['id' => $fixture['section']->id, 'lessons' => []]]], $fixture['headers'])
        ->assertUnprocessable();

    expect($fixture['course']->refresh()->status)->toBe(CourseStatusEnum::PUBLISHED);
});

it('does not let an edited course be approved without a new submit', function () {
    $fixture = draftFixture(CourseStatusEnum::REVIEW);

    putJson("/api/course/{$fixture['course']->id}/section/{$fixture['section']->id}", ['title' => 'Renamed'], $fixture['headers'])->assertOk();

    expect(fn () => app(CourseStatusService::class)->approve($fixture['course'], $fixture['course']->user_id))
        ->toThrow(ValidationException::class);

    expect($fixture['course']->refresh()->status)->toBe(CourseStatusEnum::DRAFT);
});
