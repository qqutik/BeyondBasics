<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests\Course;

use App\Modules\Catalog\DTO\Course\CourseFilterDTO;
use App\Modules\Catalog\Enums\Course\CourseLevelEnum;
use App\Modules\Catalog\Enums\Course\CourseStatusEnum;
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

            'title' => ['sometimes', 'string', 'max:255'],
            'subtitle' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:255'],

            'price' => ['nullable', 'integer', 'min:0'],

            'level' => ['sometimes', Rule::enum(CourseLevelEnum::class)],
            'language' => ['sometimes', Rule::enum(LanguageEnum::class)],
            //            'currency' => ['sometimes', Rule::enum(CurrencyEnum::class)],
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
            subtitle: $this->filled('subtitle') ? $this->string('subtitle')->toString() : null,
            description: $this->filled('description') ? $this->string('description')->toString() : null,

            price: $this->filled('price') ? $this->integer('price') : null,

            level: $this->filled('level') ? $this->string('level')->toString() : null,
            language: $this->filled('language') ? $this->string('language')->toString() : null,
            status: $this->filled('status') ? $this->string('status')->toString() : null,

            perPage: $this->integer('per_page', Config::integer('catalog.pagination.per_page')),
        );
    }

    private function currentUserId(): int
    {
        $id = Auth::id();

        return is_numeric($id) ? (int) $id : 0;
    }
}
