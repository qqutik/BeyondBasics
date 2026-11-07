<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\DTO\User\UserProfileDTO;
use App\Modules\Identity\Http\Requests\User\ProfileRequest;
use App\Modules\Identity\Http\Resources\UserProfileResource;
use App\Modules\Identity\Http\Resources\UserResource;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\UserService;
use Illuminate\Http\Request;

final class UserController
{
    public function __construct(private UserService $userService) {}

    public function me(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return new UserResource($user->load('roles'));
    }

    public function profile(ProfileRequest $request): UserProfileResource
    {
        $user = $request->user();

        $this->userService->syncProfile(new UserProfileDTO(
            $request->headline,
            $request->bio,
            $request->avatar_url,
            $request->website_url,
        ), $user);

        return new UserProfileResource($user->load('profile'));
    }
}
