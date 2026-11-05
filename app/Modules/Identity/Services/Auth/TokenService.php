<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services\Auth;

use App\Modules\Identity\DTO\Auth\ClientInfoDTO;
use App\Modules\Identity\DTO\Auth\TokenPairDTO;
use App\Modules\Identity\Exceptions\InvalidRefreshTokenException;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Repository\RefreshTokenRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use LogicException;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

/**
 * Access token: short-lived JWT, stateless, verified by signature only.
 * Refresh token: opaque random string, stored as a SHA-256 hash, single use (rotated on every refresh).
 * Reusing an already rotated refresh token means it leaked, so the whole family is revoked.
 */
final readonly class TokenService
{
    public function __construct(
        private RefreshTokenRepository $refreshTokens,
    ) {}

    /**
     * Token pair for a fresh login (new refresh-token family). Fires Illuminate\Auth\Events\Login.
     */
    public function issueForLogin(User $user, ClientInfoDTO $client): TokenPairDTO
    {
        $accessToken = $this->guard()->login($user);
        $refreshToken = $this->createRefreshToken($user->id, (string) Str::uuid(), $client);

        return $this->pair($accessToken, $refreshToken);
    }

    /**
     * Exchange a refresh token for a new pair (rotation).
     *
     * @throws InvalidRefreshTokenException
     */
    public function refresh(string $plainRefreshToken, ClientInfoDTO $client): TokenPairDTO
    {
        $hash = $this->hash($plainRefreshToken);

        // Decide inside a transaction with the row locked, but throw only after commit:
        // revoking the family on reuse must persist, not be rolled back by the exception.
        /** @var array{userId: int, refreshToken: string}|null $rotated */
        $rotated = DB::transaction(function () use ($hash, $client): ?array {
            $current = $this->refreshTokens->findByHashForUpdate($hash);

            if ($current === null || $current->isExpired()) {
                return null;
            }

            if ($current->isRevoked()) {
                $this->refreshTokens->revokeFamily($current->family_id);
                Log::warning('Refresh token reuse detected, token family revoked.', [
                    'user_id' => $current->user_id,
                    'family_id' => $current->family_id,
                ]);

                return null;
            }

            $plain = $this->createRefreshToken($current->user_id, $current->family_id, $client, $current->id);

            return ['userId' => $current->user_id, 'refreshToken' => $plain];
        });

        if ($rotated === null) {
            throw new InvalidRefreshTokenException;
        }

        $accessToken = $this->guard()->tokenById($rotated['userId']);

        if (! is_string($accessToken)) {
            throw new InvalidRefreshTokenException;
        }

        return $this->pair($accessToken, $rotated['refreshToken']);
    }

    /**
     * Logout from one device: blacklist the current access token, revoke its refresh-token family.
     */
    public function revoke(?string $plainRefreshToken): void
    {
        if ($plainRefreshToken !== null) {
            $token = $this->refreshTokens->findByHash($this->hash($plainRefreshToken));

            if ($token !== null) {
                $this->refreshTokens->revokeFamily($token->family_id);
            }
        }

        if ($this->guard()->check()) {
            $this->guard()->logout();
        }
    }

    /**
     * Logout everywhere (e.g. after a password reset). Access tokens already issued stay valid
     * until they expire, which is why they are short-lived (config jwt.ttl).
     */
    public function revokeAllForUser(User $user): void
    {
        $this->refreshTokens->revokeAllForUser($user->id);
    }

    /**
     * @return string the plain token, shown to the client once and never stored
     */
    private function createRefreshToken(int $userId, string $familyId, ClientInfoDTO $client, ?int $replacesId = null): string
    {
        $plain = bin2hex(random_bytes(32));

        $token = $this->refreshTokens->create(
            userId: $userId,
            familyId: $familyId,
            tokenHash: $this->hash($plain),
            expiresAt: Carbon::now()->addDays($this->refreshTtlDays()),
            ipAddress: $client->ipAddress,
            userAgent: $client->userAgent !== null ? Str::limit($client->userAgent, 509) : null,
        );

        if ($replacesId !== null) {
            $this->refreshTokens->markRotated($replacesId, $token->id);
        }

        return $plain;
    }

    private function pair(string $accessToken, string $plainRefreshToken): TokenPairDTO
    {
        return new TokenPairDTO(
            accessToken: $accessToken,
            expiresIn: $this->accessTtlMinutes() * 60,
            refreshToken: $plainRefreshToken,
            refreshExpiresIn: $this->refreshTtlDays() * 86400,
        );
    }

    private function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    private function accessTtlMinutes(): int
    {
        $ttl = config('jwt.ttl');

        return is_int($ttl) ? $ttl : 15;
    }

    private function refreshTtlDays(): int
    {
        $days = config('identity.refresh_token_ttl_days');

        return is_int($days) ? $days : 30;
    }

    private function guard(): JWTGuard
    {
        $guard = Auth::guard('api');

        if (! $guard instanceof JWTGuard) {
            throw new LogicException('The "api" guard must use the jwt driver.');
        }

        return $guard;
    }
}
