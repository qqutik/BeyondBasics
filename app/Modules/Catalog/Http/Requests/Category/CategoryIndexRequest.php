<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests\Category;

use App\Modules\Catalog\DTO\Category\CategoryFilterDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

final class CategoryIndexRequest extends FormRequest
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
            'parent_id' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'name' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function toDTO(): CategoryFilterDTO
    {
        return new CategoryFilterDTO(
            name: $this->filled('name') ? $this->string('name')->trim()->toString() : null,
            parentId: $this->filled('parent_id') ? $this->integer('parent_id') : null,
            isActive: $this->filled('is_active') ? $this->boolean('is_active') : null,
            perPage: min(
                $this->integer('per_page', Config::integer('catalog.pagination.per_page')),
                Config::integer('catalog.pagination.max_per_page'),
            ),
        );
    }
}
