<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests\Category;

use App\Modules\Catalog\DTO\Category\CategoryUpdateDTO;
use App\Modules\Catalog\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class CategoryUpdateRequest extends FormRequest
{
    /**
     * Checked before validation: a non-admin gets 403, not a list of validation errors.
     */
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category instanceof Category && Gate::allows('update', $category);
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
