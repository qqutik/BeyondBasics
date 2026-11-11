<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests\Section;

use App\Modules\Catalog\DTO\Section\SectionReorderDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * The full new order of a course's sections: {"ids": [3, 1, 2]} means 3 is first.
 * Shape only here; "exactly all sections of this course" is checked in SectionService,
 * under a lock, because it depends on the current DB state.
 */
final class SectionReorderRequest extends FormRequest
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
            'ids' => ['required', 'array', 'list'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ];
    }

    public function toDTO(): SectionReorderDTO
    {
        /** @var list<int|string> $ids */
        $ids = $this->validated('ids');

        return new SectionReorderDTO(ids: array_map(intval(...), $ids));
    }
}
