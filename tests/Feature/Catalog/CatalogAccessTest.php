<?php

declare(strict_types=1);

use App\Modules\Catalog\Enums\Course\CourseStatusEnum;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\CourseVersion;
use App\Modules\Catalog\Models\Section;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

/**
 * @return array{Authorization: string}
 */
function headersOf(User $user): array
{
    return bearer(apiLogin($user->email)['access_token']);
}

function accessCategory(): Category
{
    return Category::query()->firstOrCreate(['parent_id' => null, 'slug' => 'access'], ['name' => 'Access', 'position' => 1, 'is_active' => true]);
}

function courseOf(User $author, string $slug, CourseStatusEnum $status = CourseStatusEnum::DRAFT): Course
{
    $course = Course::query()->create(['user_id' => $author->id, 'category_id' => accessCategory()->id, 'slug' => $slug, 'title' => ucfirst($slug)]);
    $course->status = $status;
    $course->save();

    return $course;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function newCoursePayload(array $overrides = []): array
{
    return [
        'category_id' => accessCategory()->id,
        'title' => 'Laravel API',
        'level' => 'beginner',
        'language' => 'en',
        'currency' => 'USD',
        'price' => 1999,
        ...$overrides,
    ];
}

describe('courses: create', function () {
    it('lets only authors create courses', function () {
        postJson('/api/course', newCoursePayload(), headersOf(userWithRoles(Role::STUDENT)))->assertForbidden();
        postJson('/api/course', newCoursePayload(), headersOf(userWithRoles(Role::AUTHOR)))->assertCreated();
    });

    it('takes the author from the token and always starts as a draft', function () {
        $author = userWithRoles(Role::AUTHOR);
        $someoneElse = User::factory()->create();

        $id = postJson('/api/course', newCoursePayload(['user_id' => $someoneElse->id, 'status' => 'published']), headersOf($author))
            ->assertCreated()
            ->json('data.id');

        $course = Course::query()->findOrFail(is_numeric($id) ? (int) $id : 0);

        expect($course->user_id)->toBe($author->id)
            ->and($course->status)->toBe(CourseStatusEnum::DRAFT);
    });

    it('gives the same title a unique slug instead of a 500', function () {
        $headers = headersOf(userWithRoles(Role::AUTHOR));

        postJson('/api/course', newCoursePayload(), $headers)->assertCreated();
        postJson('/api/course', newCoursePayload(), $headers)->assertCreated();

        expect(Course::query()->pluck('slug')->unique())->toHaveCount(2);
    });

    it('validates the category and the title length', function () {
        postJson('/api/course', newCoursePayload(['category_id' => 999999, 'title' => str_repeat('a', 161)]), headersOf(userWithRoles(Role::AUTHOR)))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'title']);
    });
});

describe('courses: view, update, delete', function () {
    it('hides a draft from other users but shows it to the owner and admins', function () {
        $author = userWithRoles(Role::AUTHOR);
        $course = courseOf($author, 'secret');

        getJson("/api/course/{$course->id}", headersOf(User::factory()->create()))->assertForbidden();
        getJson("/api/course/{$course->id}", headersOf($author))->assertOk();
        getJson("/api/course/{$course->id}", headersOf(userWithRoles(Role::ADMIN)))->assertOk();
    });

    it('shows a course with an approved version to everyone logged in, even while it is edited again', function () {
        $course = courseOf(userWithRoles(Role::AUTHOR), 'public', CourseStatusEnum::PUBLISHED);
        $version = new CourseVersion(['course_id' => $course->id, 'snapshot' => ['sections' => []], 'published_at' => now()]);
        $version->version = 1;
        $version->save();
        $course->published_version_id = $version->id;
        $course->save();

        $viewer = headersOf(User::factory()->create());
        getJson("/api/course/{$course->id}", $viewer)->assertOk();

        // the author edits it: status goes back to draft, the approved version stays on sale
        $course->status = CourseStatusEnum::DRAFT;
        $course->save();
        getJson("/api/course/{$course->id}", $viewer)->assertOk();

        $course->status = CourseStatusEnum::ARCHIVED;
        $course->save();
        getJson("/api/course/{$course->id}", $viewer)->assertForbidden();
    });

    it('hides a course that was never approved, even with status published', function () {
        $course = courseOf(userWithRoles(Role::AUTHOR), 'no-version', CourseStatusEnum::PUBLISHED);

        getJson("/api/course/{$course->id}", headersOf(User::factory()->create()))->assertForbidden();
    });

    it('does not let another user update or delete a course', function () {
        $course = courseOf(userWithRoles(Role::AUTHOR), 'mine');
        $stranger = headersOf(userWithRoles(Role::AUTHOR));

        putJson("/api/course/{$course->id}", ['title' => 'Hijacked'], $stranger)->assertForbidden();
        deleteJson("/api/course/{$course->id}", [], $stranger)->assertForbidden();

        expect($course->refresh()->title)->toBe('Mine')
            ->and($course->trashed())->toBeFalse();
    });

    it('lets an admin edit but not delete someone else course', function () {
        $course = courseOf(userWithRoles(Role::AUTHOR), 'moderated');
        $admin = headersOf(userWithRoles(Role::ADMIN));

        putJson("/api/course/{$course->id}", ['title' => 'Fixed typo'], $admin)->assertOk();
        deleteJson("/api/course/{$course->id}", [], $admin)->assertForbidden();
    });
});

describe('courses: index', function () {
    it('lists only own courses for an author, whatever user_id is sent', function () {
        $author = userWithRoles(Role::AUTHOR);
        $other = userWithRoles(Role::AUTHOR);
        $own = courseOf($author, 'own');
        courseOf($other, 'foreign');

        $ids = getJson("/api/course?user_id={$other->id}", headersOf($author))->assertOk()->json('data.*.id');

        expect($ids)->toBe([$own->id]);
    });

    it('lists every course for an admin and lets them filter by author', function () {
        $author = userWithRoles(Role::AUTHOR);
        $first = courseOf($author, 'first');
        $second = courseOf(userWithRoles(Role::AUTHOR), 'second');
        $admin = headersOf(userWithRoles(Role::ADMIN));

        expect(getJson('/api/course', $admin)->json('data.*.id'))->toEqualCanonicalizing([$first->id, $second->id])
            ->and(getJson("/api/course?user_id={$author->id}", $admin)->json('data.*.id'))->toBe([$first->id]);
    });
});

describe('nested resources', function () {
    it('returns 404 for a section of another course under my course url', function () {
        $author = userWithRoles(Role::AUTHOR);
        $mine = courseOf($author, 'mine-nested');
        $foreign = Section::query()->create(['course_id' => courseOf(userWithRoles(Role::AUTHOR), 'foreign-nested')->id, 'title' => 'Foreign', 'position' => 1]);

        putJson("/api/course/{$mine->id}/section/{$foreign->id}", ['title' => 'Hijacked'], headersOf($author))->assertNotFound();
        deleteJson("/api/course/{$mine->id}/section/{$foreign->id}", [], headersOf($author))->assertNotFound();

        expect($foreign->refresh()->title)->toBe('Foreign');
    });
});

describe('status workflow', function () {
    it('lets only admins approve and reject', function () {
        $author = userWithRoles(Role::AUTHOR);
        $course = courseOf($author, 'in-review', CourseStatusEnum::REVIEW);

        postJson("/api/course/{$course->id}/approve", [], headersOf($author))->assertForbidden();
        postJson("/api/course/{$course->id}/reject", ['reason' => 'Not good enough yet'], headersOf($author))->assertForbidden();
    });

    it('requires a reason to reject', function () {
        $course = courseOf(userWithRoles(Role::AUTHOR), 'reject-me', CourseStatusEnum::REVIEW);

        postJson("/api/course/{$course->id}/reject", [], headersOf(userWithRoles(Role::ADMIN)))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);

        expect($course->refresh()->status)->toBe(CourseStatusEnum::REVIEW);
    });

    it('takes a course off sale while it is being edited again', function (CourseStatusEnum $editing) {
        $author = userWithRoles(Role::AUTHOR);
        $course = courseOf($author, 'on-sale', $editing);
        $version = new CourseVersion(['course_id' => $course->id, 'snapshot' => ['sections' => []], 'published_at' => now()]);
        $version->version = 1;
        $version->save();
        $course->published_version_id = $version->id;
        $course->save();

        postJson("/api/course/{$course->id}/archive", [], headersOf($author))->assertOk();

        expect($course->refresh()->status)->toBe(CourseStatusEnum::ARCHIVED)
            ->and($course->isPublished())->toBeFalse();
    })->with([
        'draft' => CourseStatusEnum::DRAFT,
        'review' => CourseStatusEnum::REVIEW,
    ]);

    it('answers 422, not 500, for a transition that is not allowed', function () {
        $author = userWithRoles(Role::AUTHOR);
        $course = courseOf($author, 'archived', CourseStatusEnum::ARCHIVED);

        postJson("/api/course/{$course->id}/submit", [], headersOf($author))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    });
});

describe('categories', function () {
    it('lets any logged-in user read categories', function () {
        accessCategory();

        getJson('/api/category', headersOf(User::factory()->create()))->assertOk();
    });

    it('lets only admins change categories', function () {
        $category = accessCategory();
        $user = headersOf(userWithRoles(Role::AUTHOR));

        postJson('/api/category', ['name' => 'Design', 'position' => 1], $user)->assertForbidden();
        putJson("/api/category/{$category->id}", ['name' => 'Renamed'], $user)->assertForbidden();
        deleteJson("/api/category/{$category->id}", [], $user)->assertForbidden();

        expect($category->refresh()->name)->toBe('Access');
    });

    it('refuses to delete a category that still has courses', function () {
        $course = courseOf(userWithRoles(Role::AUTHOR), 'keeps-category');

        deleteJson("/api/category/{$course->category_id}", [], headersOf(userWithRoles(Role::ADMIN)))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category']);
    });
});
