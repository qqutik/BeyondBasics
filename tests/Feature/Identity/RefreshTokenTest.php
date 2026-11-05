<?php

declare(strict_types=1);

use App\Modules\Identity\Models\RefreshToken;
use App\Modules\Identity\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\travel;

it('rotates the refresh token and issues a new access token', function () {
    $user = User::factory()->create();
    $login = apiLogin($user->email);

    $refreshed = tokensFrom(postJson('/api/auth/refresh', ['refresh_token' => $login['refresh_token']])->assertOk());

    expect($refreshed['refresh_token'])->not->toBe($login['refresh_token'])
        ->and($refreshed['access_token'])->toBeString();

    $old = RefreshToken::query()->where('token_hash', hash('sha256', $login['refresh_token']))->sole();
    $new = RefreshToken::query()->where('token_hash', hash('sha256', $refreshed['refresh_token']))->sole();

    expect($old->revoked_at)->not->toBeNull()
        ->and($old->replaced_by_id)->toBe($new->id)
        ->and($new->family_id)->toBe($old->family_id)
        ->and($new->revoked_at)->toBeNull();

    getJson('/api/me', bearer($refreshed['access_token']))->assertOk();
});

it('stores only a hash of the refresh token', function () {
    $user = User::factory()->create();
    $login = apiLogin($user->email);

    expect(RefreshToken::query()->where('token_hash', $login['refresh_token'])->exists())->toBeFalse()
        ->and(RefreshToken::query()->where('token_hash', hash('sha256', $login['refresh_token']))->exists())->toBeTrue();
});

it('revokes the whole family when a rotated refresh token is reused', function () {
    $user = User::factory()->create();
    $login = apiLogin($user->email);

    $refreshed = tokensFrom(postJson('/api/auth/refresh', ['refresh_token' => $login['refresh_token']])->assertOk());

    // An attacker replays the stolen, already used token.
    postJson('/api/auth/refresh', ['refresh_token' => $login['refresh_token']])->assertUnauthorized();

    // The legitimate client's newest token is dead too: the family is revoked.
    postJson('/api/auth/refresh', ['refresh_token' => $refreshed['refresh_token']])->assertUnauthorized();

    expect(RefreshToken::query()->whereNull('revoked_at')->count())->toBe(0);
});

it('keeps other devices logged in when one family is revoked', function () {
    $user = User::factory()->create();
    $phone = apiLogin($user->email);
    $laptop = apiLogin($user->email);

    postJson('/api/auth/refresh', ['refresh_token' => $phone['refresh_token']])->assertOk();
    postJson('/api/auth/refresh', ['refresh_token' => $phone['refresh_token']])->assertUnauthorized();

    postJson('/api/auth/refresh', ['refresh_token' => $laptop['refresh_token']])->assertOk();
});

it('rejects an expired refresh token', function () {
    $user = User::factory()->create();
    $login = apiLogin($user->email);

    travel(31)->days();

    postJson('/api/auth/refresh', ['refresh_token' => $login['refresh_token']])->assertUnauthorized();
});

it('rejects an unknown refresh token', function () {
    postJson('/api/auth/refresh', ['refresh_token' => str_repeat('a', 64)])->assertUnauthorized();
});

it('validates the refresh token format', function () {
    postJson('/api/auth/refresh', ['refresh_token' => 'short'])->assertUnprocessable();
});
