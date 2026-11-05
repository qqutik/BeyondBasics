<?php

declare(strict_types=1);

namespace App\Modules\Identity\Listeners;

use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\UserService;
use Illuminate\Auth\Events\Login;

final readonly class RecordLastLoginListener
{
    public function __construct(
        private UserService $userService,
    ) {}

    public function handle(Login $event): void
    {
        if ($event->user instanceof User) {
            $this->userService->recordLogin($event->user);
        }
    }
}
