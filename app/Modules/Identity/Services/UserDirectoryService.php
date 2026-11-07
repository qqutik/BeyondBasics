<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Contracts\UserDirectoryContract;
use App\Modules\Identity\DTO\User\UserDirectoryDTO;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Models\UserRole;
use App\Modules\Identity\Repository\UserRepository;

final readonly class UserDirectoryService implements UserDirectoryContract
{
    public function __construct(private UserRepository $repository) {}

    public function findOneById(int $userId): ?UserDirectoryDTO
    {
        $user = $this->repository->findOneById($userId);

        return $user !== null ? $this->toData($user) : null;
    }

    public function findOneByUuid(string $uuid): ?UserDirectoryDTO
    {
        $user = $this->repository->findOneByUuid($uuid);

        return $user !== null ? $this->toData($user) : null;
    }

    public function findMany(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return User::query()
            ->with('roles')
            ->whereKey(array_unique($userIds))
            ->get()
            ->mapWithKeys(fn (User $user): array => [$user->id => $this->toData($user)])
            ->all();
    }

    public function hasRole(int $userId, Role $role): bool
    {
        return UserRole::query()
            ->where('user_id', $userId)
            ->where('role', $role->value)
            ->exists();
    }

    private function toData(User $user): UserDirectoryDTO
    {
        return new UserDirectoryDTO(
            id: $user->id,
            uuid: $user->uuid,
            name: $user->name,
            email: $user->email,
            emailVerified: $user->hasVerifiedEmail(),
            roles: $user->roles->map(fn (UserRole $role): Role => $role->role)->values()->all(),
        );
    }
}
