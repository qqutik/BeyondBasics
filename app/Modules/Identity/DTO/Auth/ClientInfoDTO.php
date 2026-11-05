<?php

declare(strict_types=1);

namespace App\Modules\Identity\DTO\Auth;

use App\Modules\Identity\DTO\BaseDTO;

/**
 * Where a token was issued from; stored with the refresh token (audit, future "active devices" list).
 */
final class ClientInfoDTO extends BaseDTO
{
    public function __construct(
        public readonly ?string $ipAddress,
        public readonly ?string $userAgent,
    ) {}
}
