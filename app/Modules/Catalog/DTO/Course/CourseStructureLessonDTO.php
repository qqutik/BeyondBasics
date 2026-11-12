<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTO\Course;

use App\Modules\Catalog\DTO\BaseDTO;
use App\Modules\Catalog\Enums\Lesson\LessonTypeEnum;

final class CourseStructureLessonDTO extends BaseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $position,
        public readonly LessonTypeEnum $type,
        public readonly string $title,
        public readonly ?string $body,
        public readonly ?int $mediaId,
        public readonly int $durationSec,
        public readonly bool $isPreview,
        public readonly ?int $unlockAfterDays,
    ) {}

    /**
     * Available to a student this many days after purchase; true when there is no delay.
     */
    public function isUnlockedAfter(int $daysSincePurchase): bool
    {
        return $this->unlockAfterDays === null || $daysSincePurchase >= $this->unlockAfterDays;
    }

    /**
     * @param  array<array-key, mixed>  $data  one lesson of course_versions.snapshot
     */
    public static function fromSnapshot(array $data): self
    {
        $type = is_string($data['type'] ?? null) ? LessonTypeEnum::tryFrom($data['type']) : null;

        return new self(
            id: is_numeric($data['id'] ?? null) ? (int) $data['id'] : 0,
            position: is_numeric($data['position'] ?? null) ? (int) $data['position'] : 0,
            type: $type ?? LessonTypeEnum::TEXT,
            title: is_string($data['title'] ?? null) ? $data['title'] : '',
            body: is_string($data['body'] ?? null) ? $data['body'] : null,
            mediaId: is_numeric($data['media_id'] ?? null) ? (int) $data['media_id'] : null,
            durationSec: is_numeric($data['duration_sec'] ?? null) ? (int) $data['duration_sec'] : 0,
            isPreview: (bool) ($data['is_preview'] ?? false),
            unlockAfterDays: is_numeric($data['unlock_after_days'] ?? null) ? (int) $data['unlock_after_days'] : null,
        );
    }
}
