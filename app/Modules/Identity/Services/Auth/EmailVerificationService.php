<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services\Auth;

use App\Modules\Identity\Models\User;
use App\Modules\Identity\Repository\UserRepository;
use Illuminate\Auth\Events\Verified;

final readonly class EmailVerificationService
{
    public function __construct(
        private UserRepository $repository,
    ) {}

    /**
     * The link is authenticated by its signature (route middleware "signed"), not by a token:
     * users usually open it from their mail client, without the SPA's access token.
     *
     * @return bool false when the link does not belong to this user's current email
     */
    public function verify(int $userId, string $hash): bool
    {
        $user = $this->repository->findById($userId);

        if ($user === null || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return false;
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return true;
    }

    /**
     * @return bool false when the email is already verified
     */
    public function resend(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        $user->sendEmailVerificationNotification();

        return true;
    }
}
