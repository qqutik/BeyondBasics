<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources\Catalog;

use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\Lesson;
use App\Modules\Catalog\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public course page: the card + description + current curriculum (live sections and lessons,
 * like the header: a published course is edited without re-moderation).
 * Lessons show only what a visitor may see before buying: no body, no media.
 */
final class CatalogCourseDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Course $course */
        $course = $this->resource;

        return [
            ...(new CatalogCourseResource($course))->toArray($request),
            'description' => $course->description,
            'sections' => $course->sections->map(static fn (Section $section): array => [
                'title' => $section->title,
                'position' => $section->position,
                'lessons' => $section->lessons->map(static fn (Lesson $lesson): array => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'type' => $lesson->type->value,
                    'duration_sec' => $lesson->duration_sec,
                    'is_preview' => $lesson->is_preview,
                ])->all(),
            ])->all(),
        ];
    }
}
