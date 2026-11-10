<?php

namespace App\Modules\Catalog\Filters\Category;

use App\Modules\Catalog\Filters\BaseSearch;
use App\Modules\Catalog\Models\Category;

class CategorySearch
{
    const string MODEL = Category::class;
    use BaseSearch;
}
