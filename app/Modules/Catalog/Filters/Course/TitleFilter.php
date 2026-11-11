<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Filters\Course;

use App\Modules\Catalog\DTO\Category\CategoryFilterDTO;
use App\Modules\Catalog\Filters\QueryFilterInterface;
use App\Modules\Catalog\Helpers\LikeHelper;
use App\Modules\Catalog\Models\Category;
use Illuminate\Database\Eloquent\Builder;

/**
 * @implements QueryFilterInterface<Category, CategoryFilterDTO>
 */
final class TitleFilter implements QueryFilterInterface
{
    public function apply(Builder $query, object $filter): void
    {
        if ($filter->title === null) {
            return;
        }

        $query->where('title', 'ILIKE', LikeHelper::contains($filter->title));
    }
}
