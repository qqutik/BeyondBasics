<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests\Section;

use App\Modules\Catalog\DTO\Section\SectionUpdateDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

final class SectionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * course_id and position are intentionally absent: see SectionUpdateDTO.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
        ];
    }

    public function toDTO(): SectionUpdateDTO
    {
        return new SectionUpdateDTO(
            title: $this->string('title')->toString(),
        );
    }
}
