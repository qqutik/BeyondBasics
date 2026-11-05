<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services\Auth;

use App\Modules\Identity\DTO\Auth\ClientInfoDTO;
use App\Modules\Identity\DTO\Auth\TokenPairDTO;
use App\Modules\Identity\DTO\User\UserCreateDTO;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\UserService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use LogicException;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

/**
 * Stateless authentication use cases. No sessions: every successful call returns a JWT access token
 * plus a rotating refresh token.
 */
final readonly class AuthService
{
    public function __construct(
        private UserService $userService,
        private TokenService $tokenService,
    ) {}

    /**
     * @return array{user: User, tokens: TokenPairDTO}
     */
    public function register(UserCreateDTO $dto, ClientInfoDTO $client): array
    {
        $user = $this->userService->register($dto->getName(), $dto->getEmail(), $dto->getPassword());

        // Laravel's SendEmailVerificationNotification listens to this event.
        event(new Registered($user));

        return ['user' => $user, 'tokens' => $this->tokenService->issueForLogin($user, $client)];
    }

    /**
     * @return array{user: User, tokens: TokenPairDTO}
     *
     * @throws ValidationException
     */
    public function login(string $email, string $password, ClientInfoDTO $client): array
    {
        $guard = Auth::guard('api');

        if (! $guard instanceof JWTGuard) {
            throw new LogicException('The "api" guard must use the jwt driver.');
        }

        if (! $guard->validate(['email' => $email, 'password' => $password])) {
            // Same message for "no such user" and "wrong password": no account enumeration.
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        $user = $guard->getLastAttempted();

        if (! $user instanceof User) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        return ['user' => $user, 'tokens' => $this->tokenService->issueForLogin($user, $client)];
    }

    public function refresh(string $refreshToken, ClientInfoDTO $client): TokenPairDTO
    {
        return $this->tokenService->refresh($refreshToken, $client);
    }

    public function logout(?string $refreshToken): void
    {
        $this->tokenService->revoke($refreshToken);
    }
}
