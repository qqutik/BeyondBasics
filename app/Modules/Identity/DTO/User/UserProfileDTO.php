<?php

declare(strict_types=1);

namespace App\Modules\Identity\DTO\User;

use App\Modules\Identity\DTO\BaseDTO;

class UserProfileDTO extends BaseDTO
{
    public function __construct(
        public readonly string $headline,
        public readonly string $bio,
        public readonly ?string $avatar_url,
        public readonly ?string $website_url,
    ) {}
}
