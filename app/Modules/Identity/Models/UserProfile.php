<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Enums\Role;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
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
#[Table('user_profiles')]
#[Fillable(['user_id', 'headline', 'bio', 'avatar_url', 'website_url'])]
final class UserProfile extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [

        ];
    }
}
