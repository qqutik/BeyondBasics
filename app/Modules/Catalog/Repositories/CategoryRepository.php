<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\DTO\Category\CategoryStoreDTO;
use App\Modules\Catalog\DTO\Category\CategoryUpdateDTO;
use App\Modules\Catalog\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class CategoryRepository
{

    public function findAll(Request $request): ?Builder
    {
        return Category::search($request);
    }

    public function findOneById(int $id): ?Category
    {
        return Category::where('id', $id)->first();
    }

    public function store(CategoryStoreDTO $DTO): Category
    {
        return Category::create($DTO->toArray());
    }

    public function update(CategoryUpdateDTO $DTO, Category $category): Category
    {
        $category->update($DTO->toArray());
        return $category;
    }

    public function destroy(Category $category): Category
    {
        $category->delete();
        return $category;
    }
}
