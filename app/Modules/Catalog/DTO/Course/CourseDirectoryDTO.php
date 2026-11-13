<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTO\Course;

use App\Modules\Catalog\DTO\BaseDTO;
use App\Modules\Catalog\Enums\Course\CourseStatusEnum;
use App\Modules\Catalog\Enums\CurrencyEnum;
use DateTimeImmutable;

/**
 * Public snapshot of a course for other modules: what Billing, Enrollment or Reviews need to know.
 * No description, no draft structure, no internals.
 */
final class CourseDirectoryDTO extends BaseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly int $authorId,
        public readonly ?int $categoryId,
        public readonly string $slug,
        public readonly string $title,
        public readonly CourseStatusEnum $status,
        public readonly int $price,
        public readonly CurrencyEnum $currency,
        public readonly ?int $publishedVersionId,
        public readonly ?DateTimeImmutable $publishedAt,
    ) {}

    /**
     * On sale: has an approved version and is not archived. Not status based: a published course that is
     * being edited again (status draft / review) still sells its last approved version.
     */
    public function isPublished(): bool
    {
        return $this->publishedVersionId !== null && $this->status !== CourseStatusEnum::ARCHIVED;
    }

    public function isFree(): bool
    {
        return $this->price === 0;
    }

    public function isOwnedBy(int $userId): bool
    {
        return $this->authorId === $userId;
    }
}
