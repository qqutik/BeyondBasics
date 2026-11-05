<?php

declare(strict_types=1);

namespace App\Modules\Identity\Exceptions;

use Illuminate\Auth\AuthenticationException;

/**
 * Unknown, expired, revoked or reused refresh token. Rendered as 401 like any AuthenticationException.
 */
final class InvalidRefreshTokenException extends AuthenticationException
{
    public function __construct()
    {
        parent::__construct('Invalid refresh token.');
    }
}
