<?php

declare(strict_types=1);

use App\Modules\Identity\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

it('blacklists the access token and revokes the refresh token', function () {
    $user = User::factory()->create();
    $tokens = apiLogin($user->email);
    $auth = bearer($tokens['access_token']);

    postJson('/api/auth/logout', ['refresh_token' => $tokens['refresh_token']], $auth)->assertNoContent();
    app('auth')->forgetGuards();

    getJson('/api/me', $auth)->assertUnauthorized();
    postJson('/api/auth/refresh', ['refresh_token' => $tokens['refresh_token']])->assertUnauthorized();
});

it('requires authentication', function () {
    postJson('/api/auth/logout')->assertUnauthorized();
});
