<?php

declare(strict_types=1);

use App\Modules\Catalog\Enums\Course\CourseStatusEnum;
use App\Modules\Catalog\Enums\Lesson\LessonTypeEnum;
use App\Modules\Catalog\Events\CourseArchivedEvent;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\Lesson;
use App\Modules\Catalog\Models\Section;
use App\Modules\Catalog\Services\CourseStatusService;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

/**
 * A course with 2 sections (lessons in the first one), owned by a logged-in author.
 *
 * @return array{author: User, headers: array{Authorization: string}, course: Course, section: Section}
 */
function editorFixture(CourseStatusEnum $status = CourseStatusEnum::DRAFT): array
{
    $author = userWithRoles(Role::AUTHOR);
    $course = Course::query()->create(['user_id' => $author->id, 'slug' => 'editor', 'title' => 'Editor']);

    $first = Section::query()->create(['course_id' => $course->id, 'title' => 'First', 'position' => 1]);
    Section::query()->create(['course_id' => $course->id, 'title' => 'Second', 'position' => 2]);

    foreach (['B' => 2, 'A' => 1] as $title => $position) {
        $lesson = new Lesson(['type' => LessonTypeEnum::TEXT, 'title' => $title, 'body' => "Body {$title}"]);
        $lesson->section_id = $first->id;
        $lesson->position = $position;
        $lesson->save();
    }

    $course->status = $status;
    $course->save();

    return ['author' => $author, 'headers' => bearer(apiLogin($author->email)['access_token']), 'course' => $course, 'section' => $first];
}

describe('curriculum for the editor', function () {
    it('returns sections and lessons in order, with lesson content', function () {
        ['headers' => $h, 'course' => $course] = editorFixture();

        getJson("/api/course/{$course->id}/curriculum", $h)
            ->assertOk()
            ->assertJsonPath('data.0.title', 'First')
            ->assertJsonPath('data.0.lessons.0.title', 'A')
            ->assertJsonPath('data.0.lessons.0.body', 'Body A')
            ->assertJsonPath('data.1.title', 'Second')
            ->assertJsonCount(0, 'data.1.lessons');
    });

    it('is not available to other users', function () {
        ['course' => $course] = editorFixture();

        getJson("/api/course/{$course->id}/curriculum", bearer(apiLogin(User::factory()->create()->email)['access_token']))
            ->assertForbidden();
    });

    it('stays readable for an archived course', function () {
        ['headers' => $h, 'course' => $course] = editorFixture(CourseStatusEnum::ARCHIVED);

        getJson("/api/course/{$course->id}/curriculum", $h)->assertOk();
    });
});

describe('archived course is read-only', function () {
    it('rejects edits of the course, its sections and lessons with a clear message', function () {
        ['headers' => $h, 'course' => $course, 'section' => $section] = editorFixture(CourseStatusEnum::ARCHIVED);

        putJson("/api/course/{$course->id}", ['title' => 'New'], $h)
            ->assertForbidden()
            ->assertJsonPath('message', 'An archived course cannot be edited. Restore it first.');
        putJson("/api/course/{$course->id}/section/{$section->id}", ['title' => 'New'], $h)->assertForbidden();
        postJson("/api/course/{$course->id}/section/{$section->id}/lesson", ['type' => 'text', 'title' => 'New', 'body' => 'x'], $h)->assertForbidden();

        expect($course->refresh()->title)->toBe('Editor')
            ->and($section->refresh()->title)->toBe('First');
    });

    it('can be edited again after restore', function () {
        ['headers' => $h, 'course' => $course] = editorFixture(CourseStatusEnum::ARCHIVED);

        postJson("/api/course/{$course->id}/restore", [], $h)->assertOk();
        putJson("/api/course/{$course->id}", ['title' => 'Back again'], $h)->assertOk();
    });
});

describe('events', function () {
    it('dispatches CourseArchivedEvent only after the transaction commits', function () {
        Event::fake([CourseArchivedEvent::class]);
        ['course' => $course] = editorFixture(CourseStatusEnum::PUBLISHED);

        DB::beginTransaction();
        app(CourseStatusService::class)->archive($course);
        Event::assertNotDispatched(CourseArchivedEvent::class);
        DB::commit();

        Event::assertDispatched(CourseArchivedEvent::class, fn (CourseArchivedEvent $e): bool => $e->courseId === $course->id);
    });
});
