<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests\Course;

use App\Modules\Catalog\DTO\Course\CurriculumDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * The whole structure of a course after a drag-and-drop in the editor:
 *
 *     {"sections": [{"id": 2, "lessons": [12, 10]}, {"id": 1, "lessons": [11, 13]}]}
 *
 * The order in the arrays IS the order: positions are indexes, so they cannot be duplicated or have gaps.
 * A lesson listed under another section has been moved there.
 *
 * Shape only here; "exactly all sections and lessons of this course" is checked in CurriculumService,
 * under a lock, because it depends on the current DB state.
 */
final class CurriculumUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sections' => ['present', 'array', 'list'],
            'sections.*.id' => ['required', 'integer', 'distinct'],
            'sections.*.lessons' => ['present', 'array', 'list'],
            // distinct across all sections: one lesson cannot be in two places
            'sections.*.lessons.*' => ['required', 'integer', 'distinct'],
        ];
    }

    public function toDTO(): CurriculumDTO
    {
        /** @var list<array{id: int|string, lessons: list<int|string>}> $sections */
        $sections = $this->validated('sections');

        return CurriculumDTO::fromArray($sections);
    }
}
