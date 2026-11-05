<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Events\RoleGrantedEvent;
use App\Modules\Identity\Events\RoleRevokedEvent;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Models\UserRole;

final class RoleService
{
    /**
     * Grant a role. Idempotent: granting a role the user already has is a no-op.
     * Uniqueness is guaranteed by the (user_id, role) primary key, not by a PHP check,
     * so two concurrent grants cannot create duplicates.
     *
     * @return bool true if the role was newly granted
     */
    public function grant(User $user, Role $role, ?User $grantedBy = null): bool
    {
        $inserted = UserRole::query()->insertOrIgnore([
            'user_id' => $user->id,
            'role' => $role->value,
            'granted_by' => $grantedBy?->id,
            'granted_at' => now(),
        ]) > 0;

        if ($inserted) {
            $user->unsetRelation('roles');
            RoleGrantedEvent::dispatch($user->id, $role, $grantedBy?->id);
        }

        return $inserted;
    }

    /**
     * @return bool true if the user had the role and it was removed
     */
    public function revoke(User $user, Role $role, ?User $revokedBy = null): bool
    {
        $deleted = UserRole::query()
            ->where('user_id', $user->id)
            ->where('role', $role->value)
            ->delete() > 0;

        if ($deleted) {
            $user->unsetRelation('roles');
            RoleRevokedEvent::dispatch($user->id, $role, $revokedBy?->id);
        }

        return $deleted;
    }
}
