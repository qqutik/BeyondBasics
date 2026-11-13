<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests\Course;

use App\Modules\Catalog\DTO\Course\CourseFilterDTO;
use App\Modules\Catalog\Enums\Course\CourseLevelEnum;
use App\Modules\Catalog\Enums\Course\CourseStatusEnum;
use App\Modules\Catalog\Enums\CurrencyEnum;
use App\Modules\Catalog\Enums\LanguageEnum;
use App\Modules\Catalog\Models\Course;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
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

            'title' => ['sometimes', 'string', 'max:160'],

            // minor units, like courses.price
            'price_min' => ['sometimes', 'integer', 'min:0'],
            // gte only when price_min is sent: Laravel's gte fails when the other field is missing
            'price_max' => ['sometimes', 'integer', 'min:0', Rule::when($this->filled('price_min'), ['gte:price_min'])],

            'currency' => ['sometimes', Rule::enum(CurrencyEnum::class)],

            'level' => ['sometimes', Rule::enum(CourseLevelEnum::class)],
            'language' => ['sometimes', Rule::enum(LanguageEnum::class)],
            'status' => ['sometimes', Rule::enum(CourseStatusEnum::class)],

            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * user_id is NOT taken from the client for regular users: an author always sees only own courses
     * (drafts of others are private). Admins may list everyone or filter by any author.
     */
    public function toDTO(): CourseFilterDTO
    {
        $userId = Gate::allows('viewAll', Course::class)
            ? ($this->filled('user_id') ? $this->integer('user_id') : null)
            : $this->currentUserId();

        return new CourseFilterDTO(
            user_id: $userId,
            category_id: $this->filled('category_id') ? $this->integer('category_id') : null,
            title: $this->filled('title') ? $this->string('title')->toString() : null,
            price_min: $this->filled('price_min') ? $this->integer('price_min') : null,
            price_max: $this->filled('price_max') ? $this->integer('price_max') : null,
            currency: $this->enum('currency', CurrencyEnum::class),
            level: $this->enum('level', CourseLevelEnum::class),
            language: $this->enum('language', LanguageEnum::class),
            status: $this->enum('status', CourseStatusEnum::class),
            perPage: $this->integer('per_page', Config::integer('catalog.pagination.per_page')),
        );
    }

    private function currentUserId(): int
    {
        $id = Auth::id();

        return is_numeric($id) ? (int) $id : 0;
    }
}
