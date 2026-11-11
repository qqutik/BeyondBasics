<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Listeners;

use App\Modules\Catalog\Events\CourseDeclinedEvent;
use App\Modules\Catalog\Events\CoursePublishedEvent;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class CourseMailListener implements ShouldDispatchAfterCommit
{
    public function handle(CoursePublishedEvent|CourseDeclinedEvent $event)
    {
        //send mail
    }

}
