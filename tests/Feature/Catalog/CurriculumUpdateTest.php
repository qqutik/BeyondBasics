<?php

declare(strict_types=1);

use App\Modules\Catalog\Enums\Lesson\LessonTypeEnum;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\Lesson;
use App\Modules\Catalog\Models\Section;
use App\Modules\Identity\Models\User;

use function Pest\Laravel\putJson;

function curriculumLesson(Section $section, string $title, int $position): Lesson
{
    $lesson = new Lesson(['type' => LessonTypeEnum::TEXT, 'title' => $title]);
    $lesson->section_id = $section->id;
    $lesson->position = $position;
    $lesson->save();

    return $lesson;
}

/**
 * Course with S1 [A, B, C] and S2 [X], owned by a logged-in author.
 *
 * @return array{headers: array{Authorization: string}, course: Course, s1: Section, s2: Section, a: Lesson, b: Lesson, c: Lesson, x: Lesson}
 */
function curriculumFixture(): array
{
    $author = User::factory()->create();
    $course = Course::query()->create(['user_id' => $author->id, 'slug' => 'curriculum', 'title' => 'Curriculum']);

    $s1 = Section::query()->create(['course_id' => $course->id, 'title' => 'S1', 'position' => 1]);
    $s2 = Section::query()->create(['course_id' => $course->id, 'title' => 'S2', 'position' => 2]);

    return [
        'headers' => bearer(apiLogin($author->email)['access_token']),
        'course' => $course, 's1' => $s1, 's2' => $s2,
        'a' => curriculumLesson($s1, 'A', 1), 'b' => curriculumLesson($s1, 'B', 2), 'c' => curriculumLesson($s1, 'C', 3),
        'x' => curriculumLesson($s2, 'X', 1),
    ];
}

/**
 * Current structure as [section title => [lesson title => position]], sections in position order.
 *
 * @return array<string, array<string, int>>
 */
function structureOf(Course $course): array
{
    $result = [];

    foreach (Section::query()->where('course_id', $course->id)->orderBy('position')->with('lessons')->get() as $section) {
        $result[$section->title] = $section->lessons->mapWithKeys(fn (Lesson $l): array => [$l->title => $l->position])->all();
    }

    return $result;
}

function curriculumUrl(Course $course): string
{
    return "/api/course/{$course->id}/curriculum";
}

it('reorders sections, reorders lessons and moves a lesson in one request', function () {
    ['headers' => $h, 'course' => $course, 's1' => $s1, 's2' => $s2, 'a' => $a, 'b' => $b, 'c' => $c, 'x' => $x] = curriculumFixture();

    putJson(curriculumUrl($course), ['sections' => [
        ['id' => $s2->id, 'lessons' => [$x->id, $b->id]],  // S2 first, B moved into it
        ['id' => $s1->id, 'lessons' => [$c->id, $a->id]],  // C before A
    ]], $h)
        ->assertOk()
        ->assertJsonPath('data.0.id', $s2->id)
        ->assertJsonPath('data.0.lessons.1.id', $b->id)
        ->assertJsonPath('data.0.lessons.1.section_id', $s2->id)
        ->assertJsonPath('data.1.lessons.0.id', $c->id);

    expect(structureOf($course))->toBe([
        'S2' => ['X' => 1, 'B' => 2],
        'S1' => ['C' => 1, 'A' => 2],
    ]);
});

it('allows a section to become empty', function () {
    ['headers' => $h, 'course' => $course, 's1' => $s1, 's2' => $s2, 'a' => $a, 'b' => $b, 'c' => $c, 'x' => $x] = curriculumFixture();

    putJson(curriculumUrl($course), ['sections' => [
        ['id' => $s1->id, 'lessons' => []],
        ['id' => $s2->id, 'lessons' => [$a->id, $b->id, $c->id, $x->id]],
    ]], $h)->assertOk();

    expect(structureOf($course))->toBe([
        'S1' => [],
        'S2' => ['A' => 1, 'B' => 2, 'C' => 3, 'X' => 4],
    ]);
});

it('rejects a structure that does not match the course exactly', function (string $case) {
    ['headers' => $h, 'course' => $course, 's1' => $s1, 's2' => $s2, 'a' => $a, 'b' => $b, 'c' => $c, 'x' => $x] = curriculumFixture();
    $before = structureOf($course);

    $foreignCourse = Course::query()->create(['user_id' => User::factory()->create()->id, 'slug' => 'foreign-cur', 'title' => 'Foreign']);
    $foreignSection = Section::query()->create(['course_id' => $foreignCourse->id, 'title' => 'F', 'position' => 1]);
    $foreignLesson = curriculumLesson($foreignSection, 'F1', 1);

    $sections = match ($case) {
        'missing section' => [['id' => $s1->id, 'lessons' => [$a->id, $b->id, $c->id, $x->id]]],
        'foreign section' => [['id' => $s1->id, 'lessons' => [$a->id, $b->id, $c->id]], ['id' => $s2->id, 'lessons' => [$x->id]], ['id' => $foreignSection->id, 'lessons' => []]],
        'missing lesson' => [['id' => $s1->id, 'lessons' => [$a->id, $b->id]], ['id' => $s2->id, 'lessons' => [$x->id]]],
        'foreign lesson' => [['id' => $s1->id, 'lessons' => [$a->id, $b->id, $c->id]], ['id' => $s2->id, 'lessons' => [$x->id, $foreignLesson->id]]],
        default => throw new LogicException("Unknown case {$case}"),
    };

    putJson(curriculumUrl($course), ['sections' => $sections], $h)
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('sections');

    expect(structureOf($course))->toBe($before)
        ->and($foreignLesson->refresh()->section_id)->toBe($foreignSection->id);
})->with(['missing section', 'foreign section', 'missing lesson', 'foreign lesson']);

it('rejects the same lesson in two sections', function () {
    ['headers' => $h, 'course' => $course, 's1' => $s1, 's2' => $s2, 'a' => $a, 'b' => $b, 'c' => $c, 'x' => $x] = curriculumFixture();

    putJson(curriculumUrl($course), ['sections' => [
        ['id' => $s1->id, 'lessons' => [$a->id, $b->id, $c->id]],
        ['id' => $s2->id, 'lessons' => [$x->id, $a->id]],
    ]], $h)
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('sections.0.lessons.0');
});

it('forbids changing the structure of another author course', function () {
    ['course' => $course, 's1' => $s1, 's2' => $s2, 'a' => $a, 'b' => $b, 'c' => $c, 'x' => $x] = curriculumFixture();
    $stranger = bearer(apiLogin(User::factory()->create()->email)['access_token']);
    $before = structureOf($course);

    putJson(curriculumUrl($course), ['sections' => [
        ['id' => $s2->id, 'lessons' => [$x->id]],
        ['id' => $s1->id, 'lessons' => [$c->id, $b->id, $a->id]],
    ]], $stranger)->assertForbidden();

    expect(structureOf($course))->toBe($before);
});
