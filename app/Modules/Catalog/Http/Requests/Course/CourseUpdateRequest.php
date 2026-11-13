<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests\Course;

use App\Modules\Catalog\DTO\Course\CourseUpdateDTO;
use App\Modules\Catalog\Enums\Course\CourseLevelEnum;
use App\Modules\Catalog\Enums\CurrencyEnum;
use App\Modules\Catalog\Enums\LanguageEnum;
use App\Modules\Catalog\Models\Course;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class CourseUpdateRequest extends FormRequest
{
    /**
     * Checked before validation: someone else gets 403, not a list of validation errors.
     */
    public function authorize(): bool
    {
        $course = $this->route('course');

        return $course instanceof Course && Gate::allows('update', $course);
    }

    /**
     * "sometimes": a field is validated (and updated) only when it is present in the request.
     * "nullable" only where the column is nullable and clearing it makes sense.
     * user_id and status are intentionally absent: see CourseUpdateDTO.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],

            'title' => ['sometimes', 'string', 'max:160'],
            'subtitle' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:20000'],

            'price' => ['sometimes', 'integer', 'min:0'],
            'currency' => ['sometimes', Rule::enum(CurrencyEnum::class)],

            'level' => ['sometimes', 'nullable', Rule::enum(CourseLevelEnum::class)],
            'language' => ['sometimes', 'nullable', Rule::enum(LanguageEnum::class)],
        ];
    }

    public function toDTO(): CourseUpdateDTO
    {
        return CourseUpdateDTO::fromArray($this->validated());
    }
}
