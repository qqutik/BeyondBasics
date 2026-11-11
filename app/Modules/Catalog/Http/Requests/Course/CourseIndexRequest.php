<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests\Course;

use App\Modules\Catalog\DTO\Course\CourseFilterDTO;
use App\Modules\Catalog\Enums\Course\CourseLevelEnum;
use App\Modules\Catalog\Enums\Course\CourseStatusEnum;
use App\Modules\Catalog\Enums\LanguageEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

final class CourseIndexRequest extends FormRequest
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
            'user_id' => ['sometimes', 'integer'],
            'category_id' => ['sometimes', 'integer'],

            'title' => ['sometimes', 'string', 'max:255'],
            'subtitle' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:255'],

            'price' => ['nullable', 'integer', 'min:0'],

            'level' => ['sometimes', Rule::enum(CourseLevelEnum::class)],
            'language' => ['sometimes', Rule::enum(LanguageEnum::class)],
//            'currency' => ['sometimes', Rule::enum(CurrencyEnum::class)],
            'status' => ['sometimes', Rule::enum(CourseStatusEnum::class)],

            'page' => ['nullable', 'integer', 'min:1'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function toDTO(): CourseFilterDTO
    {
        return  new CourseFilterDTO(
            user_id: $this->integer('user_id'),
            category_id: $this->integer('category_id'),

            title: $this->string('title')->toString() ?? null,
            subtitle: $this->string('subtitle')->toString() ?? null,
            description: $this->string('description')->toString() ?? null,

            price: $this->integer('price'),

            level: $this->string('level')->toString() ?? null,
            language: $this->string('language')->toString() ?? null,
            status: $this->string('status')->toString() ?? null,

            perPage: $this->integer('per_page', Config::integer('catalog.pagination.per_page')),
        );
    }
}

