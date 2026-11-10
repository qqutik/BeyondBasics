<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Requests\CategoryIndexRequest;
use App\Modules\Catalog\Http\Requests\CategoryStoreRequest;
use App\Modules\Catalog\Http\Requests\CategoryUpdateRequest;
use App\Modules\Catalog\Http\Resources\CategoryResource;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Services\CategoryService;

final class CategoryController
{
    public function __construct(private readonly CategoryService $categoryService) {}

    public function index(CategoryIndexRequest $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $categories = $this->categoryService->findAll($request->toDTO());

        return CategoryResource::collection($categories);
    }

    public function show(Category $category): CategoryResource
    {
        $category = $this->categoryService->findOneById($category->id);

        return new CategoryResource($category);
    }

    public function store(CategoryStoreRequest $request): CategoryResource
    {
        $category = $this->categoryService->store($request->toDTO());

        return new CategoryResource($category);
    }

    public function update(CategoryUpdateRequest $request, Category $category): CategoryResource
    {
        $category = $this->categoryService->update($request->toDTO(), $category);

        return new CategoryResource($category);
    }

    public function destroy(Category $category): CategoryResource
    {
        $category = $this->categoryService->destroy($category);

        return new CategoryResource($category);
    }
}
