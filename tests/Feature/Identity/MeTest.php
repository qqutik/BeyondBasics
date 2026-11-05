<?php

declare(strict_types=1);

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\RoleService;

use function Pest\Laravel\getJson;
use function Pest\Laravel\travel;

it('requires a bearer token', function () {
    getJson('/api/me')->assertUnauthorized();
});

it('returns 401, not a redirect, for a guest without Accept: application/json', function () {
    Pest\Laravel\get('/api/me')->assertUnauthorized();
});

it('rejects a malformed or forged token', function () {
    getJson('/api/me', ['Authorization' => 'Bearer not-a-jwt'])->assertUnauthorized();
});

it('rejects an expired access token', function () {
    $user = User::factory()->create();
    $token = jwtGuard()->tokenById($user->id);
    app('auth')->forgetGuards();
    expect($token)->toBeString();

    travel(16)->minutes();

    getJson('/api/me', bearer((string) $token))->assertUnauthorized();
});

it('returns the current user without internal fields', function () {
    $user = User::factory()->create();
    app(RoleService::class)->grant($user, Role::STUDENT);
    $tokens = apiLogin($user->email);

    getJson('/api/me', bearer($tokens['access_token']))
        ->assertOk()
        ->assertExactJson(['data' => [
            'uuid' => $user->uuid,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified' => true,
            'roles' => ['student'],
            'is_author' => false,
        ]]);
});
