<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\DTO\Category\CategoryStoreDTO;
use App\Modules\Catalog\DTO\Category\CategoryUpdateDTO;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Repositories\CategoryRepository;
use App\Modules\Catalog\Traits\SlugTrait;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class CategoryService
{
    use SlugTrait;

    public function __construct(private CategoryRepository $repository) {}

    public function findAll(Request $request): ?LengthAwarePaginator
    {
        $categories = $this->repository->findAll($request);

        return $categories->with(['parent','children'])
            ->orderBy('position')
            ->paginate(config('catalog.pagination.per_page'))
            ->appends($request->input());
    }

    public function findOneById(int $id): ?Category
    {
        return $this->repository->findOneById($id);
    }

    public function store(CategoryStoreDTO $DTO): Category
    {
        $DTO->setSlug($this->generateSlug($DTO->name));
        return $this->repository->store($DTO);
    }

    public function update(CategoryUpdateDTO $DTO, Category $category): Category
    {
        return $this->repository->update($DTO, $category);
    }

    public function destroy(Category $category): Category
    {
        return $this->repository->destroy($category);
    }
}
