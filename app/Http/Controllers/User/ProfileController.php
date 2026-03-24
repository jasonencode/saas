<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserProfileRequest;
use App\Http\Resources\Users\UserProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->success(UserProfileResource::make(Auth::user()));
    }

    public function update(UpdateUserProfileRequest $request): JsonResponse
    {
        $data = $request->safe()->only(['nickname', 'gender', 'birthday', 'avatar']);
        $data = array_filter($data, static fn ($item) => !blank($item));

        $user = Auth::user();
        $user->profile->update($data);

        return $this->success(UserProfileResource::make($user));
    }
}
