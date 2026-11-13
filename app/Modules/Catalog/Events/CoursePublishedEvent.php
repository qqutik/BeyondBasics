<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * A course was approved and is on sale.
 * Dispatched inside the status transaction; ShouldDispatchAfterCommit delays it until the commit,
 * so a (queued) listener never sees a course whose change was rolled back or is not visible yet.
 */
final readonly class CoursePublishedEvent implements ShouldDispatchAfterCommit
{
    public function __construct(public int $courseId) {}
}
