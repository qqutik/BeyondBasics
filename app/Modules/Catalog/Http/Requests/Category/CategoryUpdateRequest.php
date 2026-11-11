<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests\Category;

use App\Modules\Catalog\DTO\Category\CategoryUpdateDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

final class CategoryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * "sometimes": a field is validated (and updated) only when it is present in the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'is_active' => ['sometimes', 'boolean'],
            'name' => ['sometimes', 'string', 'max:255'],
            'position' => ['sometimes', 'integer', 'max:255'],
        ];
    }

    public function toDTO(): CategoryUpdateDTO
    {
        return CategoryUpdateDTO::fromArray($this->validated());
    }
}
