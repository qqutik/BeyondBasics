<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests\Section;

use App\Modules\Catalog\DTO\Section\SectionStoreDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

final class SectionStoreRequest extends FormRequest
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
            'position' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:120'],
        ];
    }

    public function toDTO(): SectionStoreDTO
    {
        return new SectionStoreDTO(
            position: $this->string('position')->toString(),
            title: $this->string('title')->toString(),
        );
    }
}
