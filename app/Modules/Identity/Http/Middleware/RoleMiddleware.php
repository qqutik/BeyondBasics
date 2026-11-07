<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Middleware;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Coarse role gate for routes: "is this user an author / admin at all?".
 * Resource-level checks ("is it YOUR course?") belong to Policies of the owning module.
 *
 * Usage (alias registered in IdentityServiceProvider):
 *   ->middleware(['auth:api', 'role:author'])        // must have the role
 *   ->middleware(['auth:api', 'role:author,admin'])  // any of the roles
 */
final class RoleMiddleware
{
    /**
     * @param  Closure(Request): Response  $next
     *
     * @throws AuthenticationException 401 when nobody is logged in
     * @throws AuthorizationException 403 when the user has none of the roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $required = $this->parseRoles(array_values($roles));

        $user = $request->user();

        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        foreach ($required as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        throw new AuthorizationException('This action requires one of the roles: '.implode(', ', $roles).'.');
    }

    /**
     * A typo in a route definition ("role:autor") must fail loudly, not silently deny everyone.
     *
     * @param  list<string>  $roles
     * @return list<Role>
     */
    private function parseRoles(array $roles): array
    {
        if ($roles === []) {
            throw new LogicException('RoleMiddleware needs at least one role, e.g. "role:author".');
        }

        return array_map(static function (string $value): Role {
            return Role::tryFrom(trim($value))
                ?? throw new LogicException("Unknown role \"{$value}\" in RoleMiddleware. Known: "
                    .implode(', ', array_map(static fn (Role $role): string => $role->value, Role::cases())).'.');
        }, $roles);
    }
}
