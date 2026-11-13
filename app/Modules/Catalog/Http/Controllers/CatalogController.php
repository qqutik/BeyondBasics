<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Requests\Catalog\CatalogIndexRequest;
use App\Modules\Catalog\Http\Resources\Catalog\CatalogCourseDetailResource;
use App\Modules\Catalog\Http\Resources\Catalog\CatalogCourseResource;
use App\Modules\Catalog\Services\Catalog\CatalogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Public catalog for students (no login). The author's editor is CourseController.
 */
final readonly class CatalogController
{
    public function __construct(private CatalogService $service) {}

    public function index(CatalogIndexRequest $request): AnonymousResourceCollection
    {
        return CatalogCourseResource::collection($this->service->findAll($request->toDTO()));
    }

    public function show(string $slug): CatalogCourseDetailResource
    {
        $course = $this->service->findOneBySlug($slug);

        abort_if($course === null, 404);

        return new CatalogCourseDetailResource($course);
    }
}
