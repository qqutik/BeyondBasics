<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTO\Course;

use App\Modules\Catalog\DTO\BaseDTO;
use App\Modules\Catalog\Enums\Course\CourseLevelEnum;
use App\Modules\Catalog\Enums\CurrencyEnum;
use App\Modules\Catalog\Enums\LanguageEnum;

/**
 * Partial update of a course (PATCH semantics): only the fields present in the request are initialized.
 * BaseDTO::toArray() skips uninitialized properties, so absent fields are never written,
 * while an explicit null is kept for nullable columns (category_id, subtitle, description, level, language).
 *
 * Deliberately NOT here:
 *  - user_id: the author of a course never changes through an update;
 *  - status / published_at / published_version_id: they change only through the publishing workflow;
 *  - slug: stays stable when the title changes, so public URLs and links keep working.
 */
final class CourseUpdateDTO extends BaseDTO
{
    public private(set) ?int $category_id;

    public private(set) string $title;

    public private(set) ?string $subtitle;

    public private(set) ?string $description;

    public private(set) ?CourseLevelEnum $level;

    public private(set) ?LanguageEnum $language;

    public private(set) int $price;

    public private(set) CurrencyEnum $currency;

    /**
     * @param  array<string, mixed>  $data  validated input, only the keys that were sent
     */
    public static function fromArray(array $data): self
    {
        $dto = new self;

        if (array_key_exists('category_id', $data)) {
            $dto->category_id = is_numeric($data['category_id']) ? (int) $data['category_id'] : null;
        }

        if (array_key_exists('title', $data) && is_string($data['title'])) {
            $dto->title = $data['title'];
        }

        if (array_key_exists('subtitle', $data)) {
            $dto->subtitle = is_string($data['subtitle']) ? $data['subtitle'] : null;
        }

        if (array_key_exists('description', $data)) {
            $dto->description = is_string($data['description']) ? $data['description'] : null;
        }

        if (array_key_exists('level', $data)) {
            $dto->level = is_string($data['level']) ? CourseLevelEnum::from($data['level']) : null;
        }

        if (array_key_exists('language', $data)) {
            $dto->language = is_string($data['language']) ? LanguageEnum::from($data['language']) : null;
        }

        if (array_key_exists('price', $data) && is_numeric($data['price'])) {
            $dto->price = (int) $data['price'];
        }

        if (array_key_exists('currency', $data) && is_string($data['currency'])) {
            $dto->currency = CurrencyEnum::from($data['currency']);
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
