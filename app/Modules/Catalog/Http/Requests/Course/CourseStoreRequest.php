<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests\Course;

use App\Modules\Catalog\DTO\Course\CourseStoreDTO;
use App\Modules\Catalog\Enums\Course\CourseLevelEnum;
use App\Modules\Catalog\Enums\CurrencyEnum;
use App\Modules\Catalog\Enums\LanguageEnum;
use App\Modules\Catalog\Models\Course;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class CourseStoreRequest extends FormRequest
{
    /**
     * Checked before validation: a non-author gets 403, not a list of validation errors.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', Course::class);
    }

    /**
     * Same limits as the DB and as CourseUpdateRequest. user_id and status are never accepted:
     * the author is the current user, a new course is always a draft.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],

            'title' => ['required', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],

            'price' => ['sometimes', 'integer', 'min:0'],
            'currency' => ['required', Rule::enum(CurrencyEnum::class)],

            'level' => ['required', Rule::enum(CourseLevelEnum::class)],
            'language' => ['required', Rule::enum(LanguageEnum::class)],
        ];
    }

    public function toDTO(): CourseStoreDTO
    {
        $userId = Auth::id();

        return new CourseStoreDTO(
            user_id: is_numeric($userId) ? (int) $userId : 0,
            category_id: $this->integer('category_id'),

            title: $this->string('title')->toString(),
            subtitle: $this->filled('subtitle') ? $this->string('subtitle')->toString() : null,
            description: $this->filled('description') ? $this->string('description')->toString() : null,

            price: $this->integer('price'),
            currency: $this->string('currency')->toString(),
            level: $this->string('level')->toString(),
            language: $this->string('language')->toString(),
        );
    }
}
