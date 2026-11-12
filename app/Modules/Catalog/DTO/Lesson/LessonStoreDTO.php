<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTO\Lesson;

use App\Modules\Catalog\DTO\BaseDTO;
use App\Modules\Catalog\Enums\Lesson\LessonTypeEnum;

/**
 * New lesson. No section_id (from the route) and no position (appended by the service).
 */
final class LessonStoreDTO extends BaseDTO
{
    public function __construct(
        public readonly LessonTypeEnum $type,
        public readonly string $title,
        public readonly ?string $body,
        public readonly ?int $media_id,
        public readonly int $duration_sec,
        public readonly bool $is_preview,
        public readonly ?int $unlock_after_days,
    ) {}
}
