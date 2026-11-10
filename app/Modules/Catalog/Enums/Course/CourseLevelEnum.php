<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums\Course;

/**
 * Who the course is for: the expected starting knowledge of a student.
 * Used for catalog filters and the badge on the course card.
 */
enum CourseLevelEnum: string
{
    case BEGINNER = 'beginner';
    case INTERMEDIATE = 'intermediate';
    case ADVANCED = 'advanced';
    case ALL_LEVELS = 'all_levels';
}
