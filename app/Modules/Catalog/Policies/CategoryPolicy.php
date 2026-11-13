<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Policies;

use App\Modules\Catalog\Models\Category;
use App\Modules\Identity\Contracts\UserDirectoryContract;
use App\Modules\Identity\Enums\Role;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Categories are a shared taxonomy: anyone logged in reads them, only admins change them.
 */
final readonly class CategoryPolicy
{
    public function __construct(
        private UserDirectoryContract $userDirectory,
    ) {}

    public function create(Authenticatable $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(Authenticatable $user, Category $category): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(Authenticatable $user, Category $category): bool
    {
        return $this->isAdmin($user);
    }

    private function isAdmin(Authenticatable $user): bool
    {
        return $this->userDirectory->hasRole($this->getUserId($user), Role::ADMIN);
    }

    private function getUserId(Authenticatable $user): int
    {
        $id = $user->getAuthIdentifier();

        return is_numeric($id) ? (int) $id : 0;
    }
}
