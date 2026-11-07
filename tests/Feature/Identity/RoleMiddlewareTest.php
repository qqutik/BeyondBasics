<?php

declare(strict_types=1);

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\RoleService;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\getJson;
use function Pest\Laravel\withoutExceptionHandling;

beforeEach(function () {
    Route::middleware(['api', 'auth:api', 'role:author'])->get('/api/_test/author-only', fn () => ['ok' => true]);
    Route::middleware(['api', 'auth:api', 'role:author,admin'])->get('/api/_test/author-or-admin', fn () => ['ok' => true]);
    Route::middleware(['api', 'role:author'])->get('/api/_test/no-auth-middleware', fn () => ['ok' => true]);
    Route::middleware(['api', 'auth:api', 'role:autor'])->get('/api/_test/typo', fn () => ['ok' => true]);
    Route::middleware(['api', 'auth:api', 'role'])->get('/api/_test/no-roles', fn () => ['ok' => true]);
});

/**
 * @return array{Authorization: string}
 */
function bearerFor(User $user, Role ...$roles): array
{
    foreach ($roles as $role) {
        app(RoleService::class)->grant($user, $role);
    }

    return bearer(apiLogin($user->email)['access_token']);
}

it('lets a user with the role through', function () {
    getJson('/api/_test/author-only', bearerFor(User::factory()->create(), Role::STUDENT, Role::AUTHOR))
        ->assertOk()
        ->assertJson(['ok' => true]);
});

it('returns 403 for a user without the role', function () {
    getJson('/api/_test/author-only', bearerFor(User::factory()->create(), Role::STUDENT))
        ->assertForbidden();
});

it('accepts any of several roles', function () {
    getJson('/api/_test/author-or-admin', bearerFor(User::factory()->create(), Role::ADMIN))->assertOk();
    app('auth')->forgetGuards();
    getJson('/api/_test/author-or-admin', bearerFor(User::factory()->create(), Role::AUTHOR))->assertOk();
    app('auth')->forgetGuards();
    getJson('/api/_test/author-or-admin', bearerFor(User::factory()->create(), Role::STUDENT))->assertForbidden();
});

it('returns 401 for a guest', function () {
    getJson('/api/_test/author-only')->assertUnauthorized();
});

it('returns 401 instead of crashing when auth middleware is missing', function () {
    getJson('/api/_test/no-auth-middleware')->assertUnauthorized();
});

it('sees a role revoked after the token was issued', function () {
    $user = User::factory()->create();
    $headers = bearerFor($user, Role::AUTHOR);

    app(RoleService::class)->revoke($user, Role::AUTHOR);
    app('auth')->forgetGuards();

    // Roles are not baked into the JWT: the check reads the DB on every request.
    getJson('/api/_test/author-only', $headers)->assertForbidden();
});

it('fails loudly on an unknown role in the route definition', function () {
    withoutExceptionHandling();

    getJson('/api/_test/typo', bearerFor(User::factory()->create(), Role::AUTHOR));
})->throws(LogicException::class, 'Unknown role "autor"');

it('fails loudly when no role is given', function () {
    withoutExceptionHandling();

    getJson('/api/_test/no-roles', bearerFor(User::factory()->create(), Role::AUTHOR));
})->throws(LogicException::class, 'needs at least one role');
