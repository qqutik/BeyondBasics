<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Enums\Lesson\LessonTypeEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $section_id
 * @property int $position
 * @property LessonTypeEnum $type
 * @property string $title
 * @property string|null $body
 * @property int|null $media_id File in the Media module. Not a relation: load it through the Media contract.
 * @property int $duration_sec
 * @property bool $is_preview
 * @property int|null $unlock_after_days
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Section $section
 */
#[Fillable([
    'type', 'title', 'body', 'media_id', 'duration_sec', 'is_preview', 'unlock_after_days',
])]
final class Lesson extends Model
{
    /**
     * section_id and position are deliberately NOT fillable: section_id comes from the route,
     * position is set by the service (append) and the reorder endpoint.
     */
    protected $attributes = [
        'duration_sec' => 0,
        'is_preview' => false,
    ];

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function isVideo(): bool
    {
        return $this->type === LessonTypeEnum::VIDEO;
    }

    /**
     * Available to a student this many days after purchase; true when there is no delay.
     */
    public function isUnlockedAfter(int $daysSincePurchase): bool
    {
        return $this->unlock_after_days === null || $daysSincePurchase >= $this->unlock_after_days;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LessonTypeEnum::class,
            'position' => 'integer',
            'media_id' => 'integer',
            'duration_sec' => 'integer',
            'is_preview' => 'boolean',
            'unlock_after_days' => 'integer',
        ];
    }
}
