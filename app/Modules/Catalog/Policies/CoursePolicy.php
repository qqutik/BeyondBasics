<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Policies;

use App\Modules\Catalog\Models\Course;
use App\Modules\Identity\Contracts\UserDirectoryContract;
use App\Modules\Identity\Enums\Role;
use Illuminate\Contracts\Auth\Authenticatable;

final readonly class CoursePolicy
{
    public function __construct(
        private UserDirectoryContract $userDirectory,
    ) {}

    public function update(Authenticatable $user, Course $course): bool
    {
        return $course->isOwnedBy($this->getUserId($user)) || $this->isAdmin($user);
    }

    public function submit(Authenticatable $user, Course $course): bool
    {
        return $course->isOwnedBy($this->getUserId($user));
    }

    public function archive(Authenticatable $user, Course $course): bool
    {
        return $course->isOwnedBy($this->getUserId($user)) || $this->isAdmin($user);
    }

    public function approve(Authenticatable $user, Course $course): bool
    {
        return $this->isAdmin($user);
    }

    public function reject(Authenticatable $user, Course $course): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(Authenticatable $user, Course $course): bool
    {
        return $course->isOwnedBy($this->getUserId($user)) || $this->isAdmin($user);
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
