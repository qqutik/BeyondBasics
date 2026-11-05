<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests\Auth;

use App\Modules\Identity\DTO\User\UserCreateDTO;
use App\Modules\Identity\Http\Requests\Concerns\NormalizesEmail;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class RegisterRequest extends FormRequest
{
    use NormalizesEmail;

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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ];
    }

    public function toDTO(): UserCreateDTO
    {
        return new UserCreateDTO(
            $this->string('name')->toString(),
            $this->email(),
            $this->string('password')->toString(),
        );
    }
}
