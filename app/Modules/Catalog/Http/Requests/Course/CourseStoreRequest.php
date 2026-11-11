<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests\Course;

use App\Modules\Catalog\DTO\Category\CategoryFilterDTO;
use App\Modules\Catalog\DTO\Course\CourseStoreDTO;
use App\Modules\Catalog\Enums\Course\CourseLevelEnum;
use App\Modules\Catalog\Enums\Course\CourseStatusEnum;
use App\Modules\Catalog\Enums\CurrencyEnum;
use App\Modules\Catalog\Enums\LanguageEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

final class CourseStoreRequest extends FormRequest
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
            'category_id' => ['required', 'integer'],

            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],

            'price' => ['integer', 'min:0'],

            'level' => ['required', Rule::enum(CourseLevelEnum::class)],
            'language' => ['required', Rule::enum(LanguageEnum::class)],
            'currency' => ['required', Rule::enum(CurrencyEnum::class)],
        ];
    }

    public function toDTO(): CourseStoreDTO
    {
        return new CourseStoreDTO(
            user_id: Auth::user()->id,
            category_id: $this->integer('category_id'),

            title: $this->string('title')->toString(),
            subtitle: $this->string('subtitle')->toString(),
            description: $this->string('description')->toString(),

            price: $this->integer('price'),
            currency: $this->string('currency')->toString(),
            level: $this->string('level')->toString(),
            language: $this->string('language')->toString(),
            status: CourseStatusEnum::DRAFT->value,
        );
    }
}
