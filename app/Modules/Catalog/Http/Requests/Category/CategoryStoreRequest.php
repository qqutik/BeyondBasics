<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests\Category;

use App\Modules\Catalog\DTO\Category\CategoryStoreDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

final class CategoryStoreRequest extends FormRequest
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
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'is_active' => ['required', 'boolean'],
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'integer', 'max:255'],
        ];
    }

    /**
     * Typed conversion: "boolean" accepts true/false/1/0/"1"/"0", boolean() turns them into a real bool.
     */
    public function toDTO(): CategoryStoreDTO
    {
        return new CategoryStoreDTO(
            $this->filled('parent_id') ? $this->integer('parent_id') : null,
            $this->string('name')->toString(),
            $this->boolean('is_active'),
            $this->integer('position'),
        );
    }
}
