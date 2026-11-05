<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Enums\Role;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A role granted to a user. Rows are never updated: a role is either granted (row exists) or revoked (row deleted).
 * The table has a composite primary key (user_id, role); Eloquent supports a single key column only,
 * so the save/select key scoping below uses both columns.
 *
 * @property int $user_id
 * @property Role $role
 * @property int|null $granted_by
 * @property Carbon $granted_at
 */
#[Table('user_roles')]
#[Fillable(['user_id', 'role', 'granted_by', 'granted_at'])]
final class UserRole extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'user_id';

    /**
     * Composite key (user_id, role): scope UPDATE/DELETE of a single model to its own row,
     * otherwise $userRole->delete() would remove every role of the user.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function setKeysForSaveQuery($query): Builder
    {
        return $query
            ->where('user_id', $this->getOriginal('user_id', $this->getAttribute('user_id')))
            ->where('role', $this->getRawOriginal('role', $this->getAttributes()['role'] ?? null));
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function setKeysForSelectQuery($query): Builder
    {
        return $this->setKeysForSaveQuery($query);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'granted_at' => 'datetime',
        ];
    }
}
