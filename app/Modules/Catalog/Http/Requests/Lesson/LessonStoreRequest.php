<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests\Lesson;

use App\Modules\Catalog\DTO\Lesson\LessonStoreDTO;
use App\Modules\Catalog\Enums\Lesson\LessonTypeEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

final class LessonStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * section_id comes from the route ({section}), position is appended by the service: neither is accepted here.
     * media_id is not required even for video: the author may create the lesson before the upload finishes
     * (it is checked on publish). It has no "exists" rule: the media table belongs to the Media module,
     * so the service verifies it through the Media contract.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(LessonTypeEnum::class)],
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required_if:type,'.LessonTypeEnum::TEXT->value, 'nullable', 'string', 'max:100000'],
            'media_id' => ['nullable', 'integer', 'min:1'],
            'duration_sec' => ['sometimes', 'integer', 'min:0'],
            'is_preview' => ['sometimes', 'boolean'],
            'unlock_after_days' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function toDTO(): LessonStoreDTO
    {
        return new LessonStoreDTO(
            type: $this->enum('type', LessonTypeEnum::class) ?? LessonTypeEnum::TEXT,
            title: $this->string('title')->toString(),
            body: $this->filled('body') ? $this->string('body')->toString() : null,
            media_id: $this->filled('media_id') ? $this->integer('media_id') : null,
            duration_sec: $this->integer('duration_sec'),
            is_preview: $this->boolean('is_preview'),
            unlock_after_days: $this->filled('unlock_after_days') ? $this->integer('unlock_after_days') : null,
        );
    }
}
