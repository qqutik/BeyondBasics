<?php

declare(strict_types=1);

namespace App\Modules\Identity\DTO\Auth;

use App\Modules\Identity\DTO\BaseDTO;

/**
 * Property names define the API response keys via BaseDTO::toArray():
 * token_type, access_token, expires_in, refresh_token, refresh_expires_in.
 */
final class TokenPairDTO extends BaseDTO
{
    public readonly string $tokenType;

    public function __construct(
        public readonly string $accessToken,
        public readonly int $expiresIn,
        public readonly string $refreshToken,
        public readonly int $refreshExpiresIn,
    ) {
        $this->tokenType = 'Bearer';
    }
}
