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

    public function findById(int $id): ?User
    {
        return User::query()->find($id);
    }
}
