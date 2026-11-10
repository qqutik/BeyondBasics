<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums\Course;

enum CourseStatusEnum: string
{
    case DRAFT = 'draft';
    case REVIEW = 'review';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
}
