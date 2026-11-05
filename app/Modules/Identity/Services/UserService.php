<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Modules\Identity\DTO\User\UserCreateDTO;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Events\UserRegisteredEvent;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Repository\UserRepository;
use Illuminate\Support\Facades\DB;

final readonly class UserService
{
    public function __construct(
        private RoleService $roleService,
        private UserRepository $repository,
    ) {}

    private function create(UserCreateDTO $DTO): User
    {
        return $this->repository->create($DTO);
    }

    /**
     * Create an account with the default Student role, atomically.
     * UserRegistered is dispatched only after the transaction commits.
     */
    public function register(string $name, string $email, string $plainPassword): User
    {
        return DB::transaction(function () use ($name, $email, $plainPassword): User {

            $user = $this->create(new UserCreateDTO($name, $email, $plainPassword));

            $this->roleService->grant($user, Role::STUDENT);

            UserRegisteredEvent::dispatch($user->id, $user->uuid, $user->name, $user->email);

            return $user;
        });
    }

    public function recordLogin(User $user): void
    {
        // Query-level update: no model events, no updated_at bump, no race with other attribute changes.
        User::query()->whereKey($user->id)->update(['last_login_at' => now()]);
    }
}
