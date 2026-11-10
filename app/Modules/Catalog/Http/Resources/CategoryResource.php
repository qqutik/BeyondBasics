<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


final class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $category = $this->resource;

        return [
            'id' => $category->id,
            'parent_id' => $category->parent_id,
            'name' => $category->name,
            'slug' => $category->slug,
            'position' => $category->position,
            'children' => self::collection($this->whenLoaded('children')),
        ];
    }
}
