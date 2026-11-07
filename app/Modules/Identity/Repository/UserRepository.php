<?php

declare(strict_types=1);

namespace App\Modules\Identity\Repository;

use App\Modules\Identity\DTO\User\UserCreateDTO;
use App\Modules\Identity\Models\User;

final readonly class UserRepository
{
    public function create(UserCreateDTO $DTO): User
    {
        return User::query()->create([
            'name' => $DTO->getName(),
            'email' => $DTO->getEmail(),
            'password' => $DTO->getPassword(),
        ]);
    }

    public function findOneById(int $userId): ?User
    {
        return User::query()->with('roles')->find($userId);
    }

    public function findOneByUuid(string $uuid): ?User
    {
        return User::query()->with('roles')->where('uuid', $uuid)->first();
    }
}
