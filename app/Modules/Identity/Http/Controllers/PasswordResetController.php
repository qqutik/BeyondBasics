<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Http\Requests\Auth\ForgotPasswordRequest;
use App\Modules\Identity\Http\Requests\Auth\ResetPasswordRequest;
use App\Modules\Identity\Services\Auth\PasswordResetService;
use Illuminate\Http\JsonResponse;

final class PasswordResetController
{
    public function __construct(
        private readonly PasswordResetService $passwordResetService,
    ) {}

    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $this->passwordResetService->sendResetLink($request->email());

        return response()->json(['message' => __('If the account exists, a reset link has been sent.')]);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $this->passwordResetService->reset($request->email(), $request->token(), $request->password());

        return response()->json(['message' => __('passwords.reset')]);
    }
}
