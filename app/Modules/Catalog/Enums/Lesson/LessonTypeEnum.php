<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums\Lesson;

/**
 * What a lesson consists of. Decides which fields are required:
 * video needs media_id (a file in the Media module), text needs body, quiz has its own questions.
 */
enum LessonTypeEnum: string
{
    case VIDEO = 'video';
    case TEXT = 'text';
    case QUIZ = 'quiz';
}
