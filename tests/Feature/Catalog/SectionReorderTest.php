<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\Section;
use App\Modules\Identity\Models\User;

use function Pest\Laravel\putJson;

/**
 * A course with three sections A(1), B(2), C(3), owned by a logged-in author.
 *
 * @return array{headers: array{Authorization: string}, course: Course, a: Section, b: Section, c: Section}
 */
function reorderFixture(): array
{
    $author = User::factory()->create();

    $course = Course::query()->create(['user_id' => $author->id, 'slug' => 'reorder-course', 'title' => 'Reorder course']);

    $make = static fn (string $title, int $position): Section => Section::query()->create([
        'course_id' => $course->id, 'title' => $title, 'position' => $position,
    ]);

    return [
        'headers' => bearer(apiLogin($author->email)['access_token']),
        'course' => $course,
        'a' => $make('A', 1),
        'b' => $make('B', 2),
        'c' => $make('C', 3),
    ];
}

/**
 * @return array<int, int> section id => position
 */
function positionsOf(Course $course): array
{
    /** @var array<int, int> */
    return Section::query()->where('course_id', $course->id)->pluck('position', 'id')->all();
}

it('rewrites the positions in the given order', function () {
    ['headers' => $headers, 'course' => $course, 'a' => $a, 'b' => $b, 'c' => $c] = reorderFixture();

    putJson("/api/course/{$course->id}/sections/order", ['ids' => [$c->id, $a->id, $b->id]], $headers)
        ->assertOk()
        ->assertJsonPath('data.0.id', $c->id)
        ->assertJsonPath('data.0.position', 1)
        ->assertJsonPath('data.2.id', $b->id);

    expect(positionsOf($course))->toEqual([$a->id => 2, $b->id => 3, $c->id => 1]);
});

it('rejects a list that is not exactly the course sections', function (string $case) {
    ['headers' => $headers, 'course' => $course, 'a' => $a, 'b' => $b, 'c' => $c] = reorderFixture();

    $foreign = Section::query()->create([
        'course_id' => Course::query()->create(['user_id' => User::factory()->create()->id, 'slug' => 'other', 'title' => 'Other'])->id,
        'title' => 'X', 'position' => 1,
    ]);

    $ids = match ($case) {
        'missing' => [$a->id, $b->id],
        'foreign' => [$a->id, $b->id, $foreign->id],
        'duplicate' => [$a->id, $a->id, $b->id],
        'extra' => [$a->id, $b->id, $c->id, $foreign->id],
        default => throw new LogicException("Unknown case {$case}"),
    };

    putJson("/api/course/{$course->id}/sections/order", ['ids' => $ids], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor($case === 'duplicate' ? 'ids.0' : 'ids');

    expect(positionsOf($course))->toEqual([$a->id => 1, $b->id => 2, $c->id => 3]);
})->with(['missing', 'foreign', 'duplicate', 'extra']);

it('forbids reordering a course of another author', function () {
    ['course' => $course, 'a' => $a, 'b' => $b, 'c' => $c] = reorderFixture();
    $stranger = bearer(apiLogin(User::factory()->create()->email)['access_token']);

    putJson("/api/course/{$course->id}/sections/order", ['ids' => [$c->id, $b->id, $a->id]], $stranger)
        ->assertForbidden();

    expect(positionsOf($course))->toEqual([$a->id => 1, $b->id => 2, $c->id => 3]);
});
