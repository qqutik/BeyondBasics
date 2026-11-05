<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests\Concerns;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Emails are stored lower-cased; normalize input before validation so the unique rule
 * and credential lookups match regardless of case.
 *
 * @mixin FormRequest
 */
trait NormalizesEmail
{
    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        if (is_string($email)) {
            $this->merge(['email' => mb_strtolower(trim($email))]);
        }
    }

    public function email(): string
    {
        return $this->string('email')->toString();
    }
}
