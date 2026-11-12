<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Requests\Section\SectionStoreRequest;
use App\Modules\Catalog\Http\Requests\Section\SectionUpdateRequest;
use App\Modules\Catalog\Http\Resources\SectionResource;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\Section;
use App\Modules\Catalog\Services\SectionService;
use Illuminate\Support\Facades\Gate;

final readonly class SectionController
{
    public function __construct(private SectionService $service) {}

    public function store(SectionStoreRequest $request, Course $course): SectionResource
    {
        Gate::authorize('update', $course);

        $section = $this->service->store($request->toDTO(), $course);

        return new SectionResource($section);
    }

    public function update(SectionUpdateRequest $request, Course $course, Section $section): SectionResource
    {
        Gate::authorize('update', $course);

        $section = $this->service->update($request->toDTO(), $section);

        return new SectionResource($section);
    }

    public function destroy(Course $course, Section $section): SectionResource
    {
        Gate::authorize('update', $course);

        $section = $this->service->destroy($section);

        return new SectionResource($section);
    }
}
