<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

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
            'parent_id' => ['nullable', 'integer','exists:categories,id'],
            'is_active' => ['required','boolean:true,false'],
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'integer', 'max:255'],
        ];
    }
}
