<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\DTO\Category\CategoryFilterDTO;
use App\Modules\Catalog\DTO\Category\CategoryStoreDTO;
use App\Modules\Catalog\DTO\Category\CategoryUpdateDTO;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Repositories\CategoryRepository;
use App\Modules\Catalog\Traits\SlugTrait;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

final readonly class CategoryService
{
    use SlugTrait;

    public function __construct(private CategoryRepository $repository) {}

    /**
     * @return LengthAwarePaginator<int, Category>
     */
    public function findAll(CategoryFilterDTO $DTO): LengthAwarePaginator
    {
        return $this->repository->findAll($DTO);
    }

    public function findOneById(int $id): ?Category
    {
        return $this->repository->findOneById($id);
    }

    /**
     * @throws ValidationException
     */
    public function store(CategoryStoreDTO $DTO): Category
    {
        $slug = $this->generateSlug($DTO->name);

        if ($slug === '') {
            throw ValidationException::withMessages(['name' => 'The name must contain at least one letter or digit.']);
        }

        $this->assertSlugIsFree($slug, $DTO->parent_id);

        $DTO->setSlug($slug);

        return $this->guardUniqueSlug(fn (): Category => $this->repository->store($DTO));
    }

    /**
     * @throws ValidationException
     */
    public function update(CategoryUpdateDTO $DTO, Category $category): Category
    {
        if ($DTO->has('parent_id') && $DTO->parent_id !== $category->parent_id) {
            $this->assertValidParent($category, $DTO->parent_id);
            // The slug does not change on update, but moving into another parent may collide with a sibling.
            $this->assertSlugIsFree($category->slug, $DTO->parent_id, $category->id);
        }

        return $this->guardUniqueSlug(fn (): Category => $this->repository->update($DTO, $category));
    }

    /**
     * @throws ValidationException
     */
    public function destroy(Category $category): Category
    {
        if ($this->repository->hasChildren($category->id)) {
            throw ValidationException::withMessages([
                'category' => 'The category has subcategories. Move or delete them first.',
            ]);
        }

        // courses.category_id is restrictOnDelete: without this check the DB error becomes a 500
        if ($this->repository->hasCourses($category->id)) {
            throw ValidationException::withMessages([
                'category' => 'The category has courses. Move them to another category first.',
            ]);
        }

        return $this->repository->destroy($category);
    }

    /**
     * A category cannot be its own parent, nor be moved under one of its descendants (that would create a cycle).
     *
     * @throws ValidationException
     */
    private function assertValidParent(Category $category, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if ($parentId === $category->id) {
            throw ValidationException::withMessages(['parent_id' => 'A category cannot be its own parent.']);
        }

        if (in_array($category->id, $this->repository->ancestorIds($parentId), true)) {
            throw ValidationException::withMessages(['parent_id' => 'A category cannot be moved under its own subcategory.']);
        }
    }

    /**
     * @throws ValidationException
     */
    private function assertSlugIsFree(string $slug, ?int $parentId, ?int $ignoreId = null): void
    {
        if ($this->repository->slugExists($slug, $parentId, $ignoreId)) {
            throw ValidationException::withMessages(['name' => 'A category with this name already exists at this level.']);
        }
    }

    /**
     * The check above is not enough under concurrency: two requests can pass it at the same time.
     * The unique index (parent_id, slug) is the real guarantee; turn its violation into a 422.
     *
     * @param  callable(): Category  $write
     *
     * @throws ValidationException
     */
    private function guardUniqueSlug(callable $write): Category
    {
        try {
            return $write();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => 'A category with this name already exists at this level.']);
        }
    }
}
