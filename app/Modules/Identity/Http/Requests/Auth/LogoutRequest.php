<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class LogoutRequest extends FormRequest
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
            // Optional: when sent, the refresh-token family of this device is revoked too.
            'refresh_token' => ['nullable', 'string', 'size:64'],
        ];
    }

    public function refreshToken(): ?string
    {
        return $this->filled('refresh_token') ? $this->string('refresh_token')->toString() : null;
    }
}
