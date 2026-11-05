<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $family_id
 * @property string $token_hash
 * @property int|null $replaced_by_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $expires_at
 * @property Carbon|null $revoked_at
 * @property Carbon $created_at
 */
#[Table('refresh_tokens')]
#[Fillable(['user_id', 'family_id', 'token_hash', 'ip_address', 'user_agent', 'expires_at'])]
final class RefreshToken extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
