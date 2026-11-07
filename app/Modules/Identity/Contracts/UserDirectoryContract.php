<?php

declare(strict_types=1);

namespace App\Modules\Identity\Contracts;

use App\Modules\Identity\DTO\User\UserDirectoryDTO;
use App\Modules\Identity\Enums\Role;

interface UserDirectoryContract
{
    public function findOneById(int $userId): ?UserDirectoryDTO;

    public function findOneByUuid(string $uuid): ?UserDirectoryDTO;

    public function findMany(array $userIds): array;

    public function hasRole(int $userId, Role $role): bool;
}
