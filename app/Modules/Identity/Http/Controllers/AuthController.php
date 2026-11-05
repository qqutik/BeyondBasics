<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\DTO\Auth\ClientInfoDTO;
use App\Modules\Identity\Http\Requests\Auth\LoginRequest;
use App\Modules\Identity\Http\Requests\Auth\LogoutRequest;
use App\Modules\Identity\Http\Requests\Auth\RefreshTokenRequest;
use App\Modules\Identity\Http\Requests\Auth\RegisterRequest;
use App\Modules\Identity\Http\Resources\UserResource;
use App\Modules\Identity\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AuthController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        ['user' => $user, 'tokens' => $tokens] = $this->authService->register($request->toDTO(), $this->client($request));

        return response()->json([
            ...$tokens->toArray(),
            'user' => new UserResource($user->load('roles')),
        ], Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        ['user' => $user, 'tokens' => $tokens] = $this->authService->login(
            $request->email(),
            $request->password(),
            $this->client($request),
        );

        return response()->json([
            ...$tokens->toArray(),
            'user' => new UserResource($user->load('roles')),
        ]);
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        return response()->json(
            $this->authService->refresh($request->refreshToken(), $this->client($request))->toArray(),
        );
    }

    public function logout(LogoutRequest $request): Response
    {
        $this->authService->logout($request->refreshToken());

        return response()->noContent();
    }

    private function client(Request $request): ClientInfoDTO
    {
        return new ClientInfoDTO($request->ip(), $request->userAgent());
    }
}
