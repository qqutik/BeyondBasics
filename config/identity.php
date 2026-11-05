<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Refresh tokens
    |--------------------------------------------------------------------------
    |
    | Access tokens are short-lived JWTs (config/jwt.php "ttl", minutes).
    | Refresh tokens are opaque random strings stored hashed in refresh_tokens,
    | rotated on every use; reusing a rotated token revokes the whole family.
    |
    */

    'refresh_token_ttl_days' => (int) env('REFRESH_TOKEN_TTL_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Frontend URL
    |--------------------------------------------------------------------------
    |
    | Base URL of the client app. Password reset links point there
    | (FRONTEND_URL/reset-password?token=...&email=...).
    |
    */

    'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),

];
