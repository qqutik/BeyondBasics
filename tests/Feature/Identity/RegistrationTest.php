<?php

declare(strict_types=1);

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Events\UserRegisteredEvent;
use App\Modules\Identity\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\postJson;

const VALID_PASSWORD = 'Str0ng-Passw0rd!';

it('registers a user with the student role and returns a token pair', function () {
    Event::fake([UserRegisteredEvent::class]);
    Notification::fake();

    $response = postJson('/api/auth/register', [
        'name' => 'Ada Lovelace',
        'email' => 'Ada@Example.com',
        'password' => VALID_PASSWORD,
        'password_confirmation' => VALID_PASSWORD,
    ])->assertCreated()
        ->assertJsonStructure(['token_type', 'access_token', 'expires_in', 'refresh_token', 'refresh_expires_in', 'user' => ['uuid', 'roles']])
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.roles', ['student'])
        ->assertJsonMissingPath('user.id');

    $user = User::query()->where('email', 'ada@example.com')->sole();

    expect($user->uuid)->toBeUuid()
        ->and($user->hasRole(Role::STUDENT))->toBeTrue()
        ->and($user->email_verified_at)->toBeNull()
        ->and($response->json('refresh_token'))->toHaveLength(64);

    Event::assertDispatchedTimes(UserRegisteredEvent::class, 1);
    Notification::assertSentTo($user, VerifyEmail::class);
    assertDatabaseCount('refresh_tokens', 1);
});

it('does not create a session or cookies', function () {
    $response = postJson('/api/auth/register', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => VALID_PASSWORD,
        'password_confirmation' => VALID_PASSWORD,
    ])->assertCreated();

    expect($response->headers->getCookies())->toBeEmpty();
});

it('rejects a duplicate email regardless of case', function () {
    User::factory()->create(['email' => 'ada@example.com']);

    postJson('/api/auth/register', [
        'name' => 'Someone Else',
        'email' => 'ADA@example.com',
        'password' => VALID_PASSWORD,
        'password_confirmation' => VALID_PASSWORD,
    ])->assertUnprocessable()->assertJsonValidationErrors('email');

    assertDatabaseCount('users', 1);
});

it('requires a confirmed password', function () {
    postJson('/api/auth/register', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => VALID_PASSWORD,
        'password_confirmation' => 'something-else',
    ])->assertUnprocessable()->assertJsonValidationErrors('password');

    assertDatabaseCount('users', 0);
    assertDatabaseCount('user_roles', 0);
});
