<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Requests\Lesson\LessonStoreRequest;
use App\Modules\Catalog\Http\Requests\Lesson\LessonUpdateRequest;
use App\Modules\Catalog\Http\Resources\LessonResource;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\Lesson;
use App\Modules\Catalog\Models\Section;
use App\Modules\Catalog\Services\LessonService;
use Illuminate\Support\Facades\Gate;

final readonly class LessonController
{
    public function __construct(private LessonService $service) {}

    public function store(LessonStoreRequest $request, Course $course, Section $section): LessonResource
    {
        Gate::authorize('update', $course);

        $lesson = $this->service->store($request->toDTO(), $section);

        return new LessonResource($lesson);
    }

    public function update(LessonUpdateRequest $request, Course $course, Section $section, Lesson $lesson): LessonResource
    {
        Gate::authorize('update', $course);

        $lesson = $this->service->update($request->toDTO(), $lesson);

        return new LessonResource($lesson);
    }

    public function destroy(Course $course, Section $section, Lesson $lesson): LessonResource
    {
        Gate::authorize('update', $course);

        $lesson = $this->service->destroy($lesson);

        return new LessonResource($lesson);
    }
}
