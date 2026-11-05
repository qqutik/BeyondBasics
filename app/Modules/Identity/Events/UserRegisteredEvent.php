<?php

declare(strict_types=1);

namespace App\Modules\Identity\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Public domain event: a new account exists and has the Student role.
 * Carries only primitives so other modules never depend on the User model.
 */
final readonly class UserRegisteredEvent implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public int $userId,
        public string $uuid,
        public string $name,
        public string $email,
    ) {}
}
