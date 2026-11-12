<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests\Lesson;

use App\Modules\Catalog\DTO\Lesson\LessonUpdateDTO;
use App\Modules\Catalog\Enums\Lesson\LessonTypeEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

final class LessonUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * "sometimes": a field is validated (and updated) only when it is present in the request.
     * section_id and position are intentionally absent: they change through move and reorder.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::enum(LessonTypeEnum::class)],
            'title' => ['sometimes', 'string', 'max:160'],
            'body' => ['sometimes', 'nullable', 'string', 'max:100000'],
            'media_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'duration_sec' => ['sometimes', 'integer', 'min:0'],
            'is_preview' => ['sometimes', 'boolean'],
            'unlock_after_days' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function toDTO(): LessonUpdateDTO
    {
        return LessonUpdateDTO::fromArray($this->validated());
    }
}
