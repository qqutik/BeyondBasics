<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources\Catalog;

use App\Modules\Catalog\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A course card in the public catalog. No internal id: the course is addressed by slug.
 */
final class CatalogCourseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Course $course */
        $course = $this->resource;

        return [
            'uuid' => $course->uuid,
            'slug' => $course->slug,
            'title' => $course->title,
            'subtitle' => $course->subtitle,
            'category_id' => $course->category_id,
            'level' => $course->level?->value,
            'language' => $course->language?->value,
            'price' => [
                'amount' => $course->price,
                'currency' => $course->currency->value,
                'formatted' => $course->currency->format($course->price),
            ],
            'published_at' => $course->published_at?->toIso8601String(),
        ];
    }
}
