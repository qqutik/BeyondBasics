<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Policies;

use App\Modules\Catalog\Models\Course;
use App\Modules\Identity\Contracts\UserDirectoryContract;
use App\Modules\Identity\Enums\Role;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Standard Laravel ability names (viewAny / view / create / update / delete), so Gate, @can and
 * authorizeResource() work without mapping. Roles are read through Identity's contract, not its models.
 */
final readonly class CoursePolicy
{
    public function __construct(
        private UserDirectoryContract $userDirectory,
    ) {}

    /**
     * Sees every course in the editor list, not only own ones.
     */
    public function viewAll(Authenticatable $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Drafts and courses under review are visible only to their author and admins.
     */
    public function view(Authenticatable $user, Course $course): bool
    {
        return $course->isPublished() || $this->ownsOrAdmin($user, $course);
    }

    /**
     * Authors create courses; admins too (e.g. a platform course). No Course instance: there is none yet.
     */
    public function create(Authenticatable $user): bool
    {
        return $this->hasRole($user, Role::AUTHOR) || $this->hasRole($user, Role::ADMIN);
    }

    /**
     * Also guards sections, lessons and curriculum of the course.
     */
    public function update(Authenticatable $user, Course $course): bool
    {
        return $this->ownsOrAdmin($user, $course);
    }

    public function delete(Authenticatable $user, Course $course): bool
    {
        return $course->isOwnedBy($this->getUserId($user));
    }

    public function submit(Authenticatable $user, Course $course): bool
    {
        return $course->isOwnedBy($this->getUserId($user));
    }

    public function archive(Authenticatable $user, Course $course): bool
    {
        return $this->ownsOrAdmin($user, $course);
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
        return $this->ownsOrAdmin($user, $course);
    }

    private function ownsOrAdmin(Authenticatable $user, Course $course): bool
    {
        return $course->isOwnedBy($this->getUserId($user)) || $this->isAdmin($user);
    }

    private function isAdmin(Authenticatable $user): bool
    {
        return $this->hasRole($user, Role::ADMIN);
    }

    private function hasRole(Authenticatable $user, Role $role): bool
    {
        return $this->userDirectory->hasRole($this->getUserId($user), $role);
    }

    private function getUserId(Authenticatable $user): int
    {
        $id = $user->getAuthIdentifier();

        return is_numeric($id) ? (int) $id : 0;
    }
}
