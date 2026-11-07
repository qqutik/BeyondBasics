<?php

declare(strict_types=1);

namespace App\Modules\Identity\DTO\User;

use App\Modules\Identity\DTO\BaseDTO;
use App\Modules\Identity\Enums\Role;

/**
 * Public snapshot of a user for other modules. No password, no tokens, no internals.
 */
final class UserDirectoryDTO extends BaseDTO
{

    /**
     * @param int $id
     * @param string $uuid
     * @param string $name
     * @param string $email
     * @param bool $emailVerified
     * @param array $roles
     */
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly string $name,
        public readonly string $email,
        public readonly bool $emailVerified,
        public readonly array $roles,
    ) {}

    public function hasRole(Role $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function isAuthor(): bool
    {
        return $this->hasRole(Role::AUTHOR);
    }
}
