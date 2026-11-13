<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums\Course;

enum CourseStatusEnum: string
{
    case DRAFT = 'draft';
    case REVIEW = 'review';
    case PUBLISHED = 'published';
    case DECLINED = 'declined';
    case ARCHIVED = 'archived';

    /**
     * Status describes the draft in the editor; whether the course is on sale is published_version_id.
     * So archive is allowed from every non-archived state: a course on sale that is being edited again
     * (draft / review) must still be removable from sale.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::REVIEW, self::ARCHIVED],
            self::REVIEW => [self::DRAFT, self::PUBLISHED, self::DECLINED, self::ARCHIVED],
            self::PUBLISHED, self::DECLINED => [self::ARCHIVED],
            self::ARCHIVED => [self::DRAFT],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }
}
