<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Filters\Category\CategorySearch;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property bool $is_active
 * @property string $slug
 * @property string $name
 * @property int $position
 */
#[Fillable(['parent_id', 'is_active', 'slug', 'name', 'position'])]
final class Category extends Model
{
    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    public function scopeSearch(Builder $builder, Request $request): Builder
    {
        return new CategorySearch()->apply($request, $builder);
    }
}
