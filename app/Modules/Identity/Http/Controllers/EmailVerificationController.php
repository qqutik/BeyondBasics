<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\Auth\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class EmailVerificationController
{
    public function __construct(
        private readonly EmailVerificationService $emailVerificationService,
    ) {}

    public function verify(int $id, string $hash): JsonResponse
    {
        abort_unless($this->emailVerificationService->verify($id, $hash), Response::HTTP_FORBIDDEN);

        return response()->json(['message' => __('Email verified.')]);
    }

    public function resend(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $sent = $this->emailVerificationService->resend($user);

        return response()->json(
            ['message' => $sent ? __('Verification link sent.') : __('Email already verified.')],
            $sent ? Response::HTTP_ACCEPTED : Response::HTTP_OK,
        );
    }
}
