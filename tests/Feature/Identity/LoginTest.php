<?php

declare(strict_types=1);

use App\Modules\Identity\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

it('logs in with valid credentials and records the login time', function () {
    $user = User::factory()->create();

    $tokens = apiLogin($user->email);

    expect($tokens['expires_in'])->toBe(15 * 60)
        ->and($user->refresh()->last_login_at)->not->toBeNull();

    getJson('/api/me', bearer($tokens['access_token']))
        ->assertOk()
        ->assertJsonPath('data.uuid', $user->uuid);
});

it('treats the email case-insensitively', function () {
    $user = User::factory()->create(['email' => 'ada@example.com']);

    postJson('/api/auth/login', ['email' => 'ADA@Example.com', 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('user.uuid', $user->uuid);
});

it('rejects a wrong password with the same error as an unknown email', function () {
    $user = User::factory()->create();

    $wrongPassword = postJson('/api/auth/login', ['email' => $user->email, 'password' => 'wrong'])
        ->assertUnprocessable()
        ->json('errors.email');
    $unknownEmail = postJson('/api/auth/login', ['email' => 'nobody@example.com', 'password' => 'wrong'])
        ->assertUnprocessable()
        ->json('errors.email');

    expect($wrongPassword)->toBe($unknownEmail)
        ->and($user->refresh()->last_login_at)->toBeNull();
});

it('does not log in a soft-deleted user', function () {
    $user = User::factory()->create();
    $user->delete();

    postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])->assertUnprocessable();
});

it('throttles login after 5 failed attempts', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        postJson('/api/auth/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnprocessable();
    }

    // Even the correct password is refused while throttled.
    postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])->assertTooManyRequests();
});
