<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\DTO\Category\CategoryFilterDTO;
use App\Modules\Catalog\DTO\Category\CategoryStoreDTO;
use App\Modules\Catalog\DTO\Category\CategoryUpdateDTO;
use App\Modules\Catalog\Filters\Category\CategoryFilters;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class CategoryRepository
{
    public function __construct(
        private readonly CategoryFilters $filters,
    ) {}

    /**
     * Includes soft-deleted courses: their rows still reference the category.
     */
    public function hasCourses(int $categoryId): bool
    {
        return Course::withTrashed()->where('category_id', $categoryId)->exists();
    }

    /**
     * @return LengthAwarePaginator<int, Category>
     */
    public function findAll(CategoryFilterDTO $filter): LengthAwarePaginator
    {
        return $this->filters
            ->apply(Category::query(), $filter)
            ->with('children')
            ->orderBy('position')
            ->paginate($filter->perPage)
            ->withQueryString();
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

    public function slugExists(string $slug, ?int $parentId, ?int $ignoreId = null): bool
    {
        return Category::query()
            ->where('slug', $slug)
            ->when(
                $parentId === null,
                fn (Builder $query) => $query->whereNull('parent_id'),
                fn (Builder $query) => $query->where('parent_id', $parentId),
            )
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    public function hasChildren(int $categoryId): bool
    {
        return Category::query()->where('parent_id', $categoryId)->exists();
    }

    /**
     * Ids of the category itself and all its ancestors up to the root, in one recursive query.
     *
     * @return list<int>
     */
    public function ancestorIds(int $categoryId): array
    {
        $rows = Category::query()->getConnection()->select(
            'WITH RECURSIVE chain AS (
                SELECT id, parent_id FROM categories WHERE id = ?
                UNION
                SELECT c.id, c.parent_id FROM categories c JOIN chain ON c.id = chain.parent_id
            )
            SELECT id FROM chain',
            [$categoryId],
        );

        $ids = [];

        foreach ($rows as $row) {
            if (is_object($row) && property_exists($row, 'id') && is_numeric($row->id)) {
                $ids[] = (int) $row->id;
            }
        }

        return $ids;
    }
}
