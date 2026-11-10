<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Filters\Category;

use App\Modules\Catalog\DTO\Category\CategoryFilterDTO;
use App\Modules\Catalog\Filters\QueryFilterInterface;
use App\Modules\Catalog\Models\Category;
use Illuminate\Database\Eloquent\Builder;

/**
 * @implements QueryFilterInterface<Category, CategoryFilterDTO>
 */
final class ParentIdFilter implements QueryFilterInterface
{
    public function apply(Builder $query, object $filter): void
    {
        if ($filter->parentId === null) {
            return;
        }

        $query->where('parent_id', $filter->parentId);
    }
}
