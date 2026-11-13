<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use App\Modules\Catalog\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CourseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Course $course */
        $course = $this->resource;

        return [
            'id' => $course->id,
            'title' => $course->title,
            'subtitle' => $course->subtitle,
            'description' => $course->description,
            'price' => $course->price,
            'level' => $course->level,
            'language' => $course->language,
            'status' => $course->status,
        ];
    }
}
