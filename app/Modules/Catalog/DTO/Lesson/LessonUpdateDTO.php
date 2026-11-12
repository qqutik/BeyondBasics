<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTO\Lesson;

use App\Modules\Catalog\DTO\BaseDTO;
use App\Modules\Catalog\Enums\Lesson\LessonTypeEnum;

/**
 * Partial update of a lesson (PATCH semantics): only the fields present in the request are initialized.
 * BaseDTO::toArray() skips uninitialized properties, so absent fields are never written,
 * while an explicit null is kept for nullable columns (body, media_id, unlock_after_days).
 *
 * Deliberately NOT here:
 *  - section_id: a lesson changes section only through the move endpoint;
 *  - position: changes only through reorder / move.
 */
final class LessonUpdateDTO extends BaseDTO
{
    public private(set) LessonTypeEnum $type;

    public private(set) string $title;

    public private(set) ?string $body;

    public private(set) ?int $media_id;

    public private(set) int $duration_sec;

    public private(set) bool $is_preview;

    public private(set) ?int $unlock_after_days;

    /**
     * @param  array<array-key, mixed>  $data  validated input, only the keys that were sent
     */
    public static function fromArray(array $data): self
    {
        $dto = new self;

        if (array_key_exists('type', $data) && is_string($data['type'])) {
            $dto->type = LessonTypeEnum::from($data['type']);
        }

        if (array_key_exists('title', $data) && is_string($data['title'])) {
            $dto->title = $data['title'];
        }

        if (array_key_exists('body', $data)) {
            $dto->body = is_string($data['body']) ? $data['body'] : null;
        }

        if (array_key_exists('media_id', $data)) {
            $dto->media_id = is_numeric($data['media_id']) ? (int) $data['media_id'] : null;
        }

        if (array_key_exists('duration_sec', $data) && is_numeric($data['duration_sec'])) {
            $dto->duration_sec = (int) $data['duration_sec'];
        }

        if (array_key_exists('is_preview', $data)) {
            $dto->is_preview = filter_var($data['is_preview'], FILTER_VALIDATE_BOOLEAN);
        }

        if (array_key_exists('unlock_after_days', $data)) {
            $dto->unlock_after_days = is_numeric($data['unlock_after_days']) ? (int) $data['unlock_after_days'] : null;
        }

        return $dto;
    }

    public function has(string $field): bool
    {
        return array_key_exists($field, $this->toArray());
    }

    public function isEmpty(): bool
    {
        return $this->toArray() === [];
    }
}
