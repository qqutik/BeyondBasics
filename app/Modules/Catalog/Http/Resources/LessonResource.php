<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use App\Modules\Catalog\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lesson as the author sees it in the course editor (the draft).
 * media_id is the raw id of the file in the Media module; the video URL is not exposed here:
 * it is a signed, short-lived link issued only by the student-facing lesson endpoint.
 */
final class LessonResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Lesson $lesson */
        $lesson = $this->resource;

        return [
            'id' => $lesson->id,
            'section_id' => $lesson->section_id,
            'position' => $lesson->position,
            'type' => $lesson->type->value,
            'title' => $lesson->title,
            'body' => $lesson->body,
            'media_id' => $lesson->media_id,
            'duration_sec' => $lesson->duration_sec,
            'is_preview' => $lesson->is_preview,
            'unlock_after_days' => $lesson->unlock_after_days,
        ];
    }
}
