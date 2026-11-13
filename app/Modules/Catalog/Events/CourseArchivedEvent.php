<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * A course was taken off sale (archived). Students who bought it keep access; other modules
 * (search, recommendations) should stop offering it.
 * ShouldDispatchAfterCommit: see CoursePublishedEvent.
 */
final readonly class CourseArchivedEvent implements ShouldDispatchAfterCommit
{
    public function __construct(public int $courseId) {}
}
