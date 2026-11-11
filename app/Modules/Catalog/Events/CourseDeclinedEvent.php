<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Events;

final readonly class CourseDeclinedEvent
{
    public function __construct(public int $courseId)
    {
    }

}
