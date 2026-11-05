<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class RefreshTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'refresh_token' => ['required', 'string', 'size:64'],
        ];
    }

    public function refreshToken(): string
    {
        return $this->string('refresh_token')->toString();
    }
}
