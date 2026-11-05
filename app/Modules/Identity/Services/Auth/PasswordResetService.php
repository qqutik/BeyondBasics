<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services\Auth;

use App\Modules\Identity\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

final readonly class PasswordResetService
{
    public function __construct(
        private TokenService $tokenService,
    ) {}

    /**
     * Always "succeeds" from the caller's point of view, so the endpoint does not reveal
     * whether an account exists. Throttled resends are silently ignored too.
     */
    public function sendResetLink(string $email): void
    {
        Password::broker()->sendResetLink(['email' => $email]);
    }

    /**
     * @throws ValidationException
     */
    public function reset(string $email, string $token, string $newPassword): void
    {
        $status = Password::broker()->reset(
            ['email' => $email, 'token' => $token, 'password' => $newPassword],
            function (User $user, string $password): void {
                $user->forceFill(['password' => $password])->save();

                // A password reset logs the account out of every device.
                $this->tokenService->revokeAllForUser($user);

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __(is_string($status) ? $status : 'passwords.token')]);
        }
    }
}
