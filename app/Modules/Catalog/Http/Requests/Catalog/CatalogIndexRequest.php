<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests\Catalog;

use App\Modules\Catalog\DTO\Course\CourseFilterDTO;
use App\Modules\Catalog\Enums\Course\CourseLevelEnum;
use App\Modules\Catalog\Enums\CurrencyEnum;
use App\Modules\Catalog\Enums\LanguageEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

/**
 * Public catalog: no login. Same CourseFilterDTO / CourseFilters as the editor list,
 * but user_id and status are never taken from the client (the catalog shows only published courses).
 */
final class CatalogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'integer'],
            'title' => ['sometimes', 'string', 'max:160'],
            'price_min' => ['sometimes', 'integer', 'min:0'],
            'price_max' => ['sometimes', 'integer', 'min:0', Rule::when($this->filled('price_min'), ['gte:price_min'])],
            'currency' => ['sometimes', Rule::enum(CurrencyEnum::class)],
            'level' => ['sometimes', Rule::enum(CourseLevelEnum::class)],
            'language' => ['sometimes', Rule::enum(LanguageEnum::class)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function toDTO(): CourseFilterDTO
    {
        return new CourseFilterDTO(
            user_id: null,
            category_id: $this->filled('category_id') ? $this->integer('category_id') : null,
            title: $this->filled('title') ? $this->string('title')->toString() : null,
            price_min: $this->filled('price_min') ? $this->integer('price_min') : null,
            price_max: $this->filled('price_max') ? $this->integer('price_max') : null,
            currency: $this->enum('currency', CurrencyEnum::class),
            level: $this->enum('level', CourseLevelEnum::class),
            language: $this->enum('language', LanguageEnum::class),
            status: null,
            perPage: $this->integer('per_page', Config::integer('catalog.pagination.per_page')),
        );
    }
}
