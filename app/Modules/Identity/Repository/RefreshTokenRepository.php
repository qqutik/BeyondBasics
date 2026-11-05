<?php

declare(strict_types=1);

namespace App\Modules\Identity\Repository;

use App\Modules\Identity\Models\RefreshToken;
use Illuminate\Support\Carbon;

final readonly class RefreshTokenRepository
{
    public function create(
        int $userId,
        string $familyId,
        string $tokenHash,
        Carbon $expiresAt,
        ?string $ipAddress,
        ?string $userAgent,
    ): RefreshToken {
        return RefreshToken::query()->create([
            'user_id' => $userId,
            'family_id' => $familyId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Row-locked lookup: two concurrent refreshes with the same token are serialized,
     * so only one of them can rotate it.
     */
    public function findByHashForUpdate(string $tokenHash): ?RefreshToken
    {
        return RefreshToken::query()
            ->where('token_hash', $tokenHash)
            ->lockForUpdate()
            ->first();
    }

    public function findByHash(string $tokenHash): ?RefreshToken
    {
        return RefreshToken::query()->where('token_hash', $tokenHash)->first();
    }

    public function markRotated(int $tokenId, int $replacementId): void
    {
        RefreshToken::query()->whereKey($tokenId)->update([
            'revoked_at' => now(),
            'replaced_by_id' => $replacementId,
        ]);
    }

    public function revokeFamily(string $familyId): int
    {
        return RefreshToken::query()
            ->where('family_id', $familyId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function revokeAllForUser(int $userId): int
    {
        return RefreshToken::query()
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }
}
