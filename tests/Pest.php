<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\postJson;

use PHPOpenSourceSaver\JWTAuth\JWTGuard;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests boot the Laravel application and run against the
| PostgreSQL test database (beyond_basics_test), refreshed per test.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Token pair from an auth endpoint response, with the types checked.
 *
 * @param  TestResponse<Response>  $response
 * @return array{access_token: string, refresh_token: string, expires_in: int}
 */
function tokensFrom(TestResponse $response): array
{
    $access = $response->json('access_token');
    $refresh = $response->json('refresh_token');
    $expiresIn = $response->json('expires_in');

    if (! is_string($access) || ! is_string($refresh) || ! is_int($expiresIn)) {
        throw new RuntimeException('Response does not contain a token pair: '.$response->getContent());
    }

    // The next HTTP call in the test should start without a user cached on the guard, like a real request.
    app('auth')->forgetGuards();

    return ['access_token' => $access, 'refresh_token' => $refresh, 'expires_in' => $expiresIn];
}

/**
 * Log in through the API.
 *
 * @return array{access_token: string, refresh_token: string, expires_in: int}
 */
function apiLogin(string $email, string $password = 'password'): array
{
    return tokensFrom(postJson('/api/auth/login', ['email' => $email, 'password' => $password])->assertOk());
}

/**
 * @return array{Authorization: string}
 */
function bearer(string $accessToken): array
{
    return ['Authorization' => 'Bearer '.$accessToken];
}

function jwtGuard(): JWTGuard
{
    $guard = Auth::guard('api');

    if (! $guard instanceof JWTGuard) {
        throw new LogicException('The "api" guard must use the jwt driver.');
    }

    return $guard;
}
