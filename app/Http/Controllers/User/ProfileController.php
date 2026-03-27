<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserProfileRequest;
use App\Http\Resources\Users\UserProfileResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(UserProfileResource::make(Auth::user()));
    }

    public function update(UpdateUserProfileRequest $request): JsonResponse
    {
        $data = $request->safe()->only(['nickname', 'gender', 'birthday', 'avatar']);
        $data = array_filter($data, static fn ($item) => ! blank($item));

        $user = Auth::user();
        $user->profile->update($data);

        return ApiResponse::success(UserProfileResource::make($user), '用户信息更新成功');
    }
}
