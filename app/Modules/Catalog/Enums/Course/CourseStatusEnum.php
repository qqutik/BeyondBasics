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
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::REVIEW],
            self::REVIEW => [self::DRAFT, self::PUBLISHED, self::DECLINED],
            self::PUBLISHED, self::DECLINED => [self::ARCHIVED],
            self::ARCHIVED => [self::DRAFT],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }
}
