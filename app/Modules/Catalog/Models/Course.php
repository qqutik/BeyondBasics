<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Enums\Course\CourseLevelEnum;
use App\Modules\Catalog\Enums\Course\CourseStatusEnum;
use App\Modules\Catalog\Enums\CurrencyEnum;
use App\Modules\Catalog\Enums\LanguageEnum;
use App\Modules\Catalog\Policies\CoursePolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $uuid Public identifier, safe to expose (the numeric id is not).
 * @property int $user_id Author (Identity user). DB-level FK only: no Eloquent relation across modules.
 * @property int|null $category_id
 * @property string $slug
 * @property string $title
 * @property string|null $subtitle
 * @property string|null $description
 * @property CourseLevelEnum|null $level
 * @property LanguageEnum|null $language
 * @property CourseStatusEnum $status
 * @property int $price Minor units (cents): 1999 + USD = $19.99. 0 = free.
 * @property CurrencyEnum $currency ISO 4217, e.g. USD
 * @property int|null $published_version_id
 * @property Carbon|null $published_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Category|null $category
 * @property-read Collection<int, Section> $sections
 * @property-read Collection<int, CourseVersion> $versions
 * @property-read CourseVersion|null $publishedVersion
 */
#[Fillable([
    'user_id', 'category_id', 'slug', 'title', 'subtitle', 'description',
    'level', 'language', 'price', 'currency',
])]
#[Hidden(['id'])]
#[UsePolicy(CoursePolicy::class)]
final class Course extends Model
{
    // uuid is generated on create; soft delete keeps the course for students who already bought it.
    use HasUuids, SoftDeletes;

    /**
     * Same defaults as the DB, so a new (unsaved) model already reports status DRAFT and price 0.
     *
     * Note: status, published_version_id and published_at are deliberately NOT fillable.
     * They change only through the publishing workflow (assigned explicitly in the service),
     * never by mass assignment from request data.
     */
    protected $attributes = [
        'status' => 'draft',
        'price' => 0,
        'currency' => CurrencyEnum::USD->value,
    ];

    /**
     * Columns filled with a UUID on create. The primary key stays an auto-increment bigint.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * Same module, so an Eloquent relation is fine. The author is NOT a relation:
     * load it through Identity's UserDirectoryContract by user_id.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Also used by scopeBindings(): course/{course}/section/{section} resolves {section} through this relation,
     * so a section of another course gives 404.
     *
     * @return HasMany<Section, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('position');
    }

    /**
     * @return HasMany<CourseVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(CourseVersion::class);
    }

    /**
     * @return BelongsTo<CourseVersion, $this>
     */
    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(CourseVersion::class, 'published_version_id');
    }

    public function isDraft(): bool
    {
        return $this->status === CourseStatusEnum::DRAFT;
    }

    /**
     * "Is on sale": the course has an approved version and is not archived.
     *
     * Deliberately NOT based on status: status describes the draft in the editor. A published course that
     * the author is editing again is status=draft (or review) but still sells its last approved version.
     */
    public function isPublished(): bool
    {
        return $this->published_version_id !== null
            && $this->status !== CourseStatusEnum::ARCHIVED
            && ! $this->trashed();
    }

    public function isFree(): bool
    {
        return $this->price === 0;
    }

    public function isOwnedBy(int $userId): bool
    {
        return $this->user_id === $userId;
    }

    /**
     * Visible in the public catalog: same rule as isPublished() (soft-deleted rows are excluded by SoftDeletes).
     *
     * @param  Builder<Course>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->whereNotNull('published_version_id')
            ->where('status', '!=', CourseStatusEnum::ARCHIVED->value);
    }

    /**
     * @param  Builder<Course>  $query
     */
    #[Scope]
    protected function ofAuthor(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => CourseLevelEnum::class,
            'language' => LanguageEnum::class,
            'status' => CourseStatusEnum::class,
            'price' => 'integer',
            'currency' => CurrencyEnum::class,
            'published_version_id' => 'integer',
            'published_at' => 'datetime',
        ];
    }
}
