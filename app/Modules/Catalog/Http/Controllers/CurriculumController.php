<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Requests\Course\CurriculumUpdateRequest;
use App\Modules\Catalog\Http\Resources\SectionResource;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Services\CurriculumService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final readonly class CurriculumController
{
    public function __construct(private CurriculumService $service) {}

    /**
     * The editor view: all sections with all lessons (including content), owner and admins only.
     */
    public function show(Course $course): AnonymousResourceCollection
    {
        Gate::authorize('viewCurriculum', $course);

        return SectionResource::collection($this->service->show($course));
    }

    public function update(CurriculumUpdateRequest $request, Course $course): AnonymousResourceCollection
    {
        Gate::authorize('update', $course);

        $sections = $this->service->update($course, $request->toDTO());

        return SectionResource::collection($sections);
    }
}
