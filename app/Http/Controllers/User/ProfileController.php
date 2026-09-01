<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateUserProfileRequest;
use App\Http\Resources\User\UserProfileResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * 获取当前用户信息
     *
     * @return JsonResponse 用户信息
     */
    public function index(): JsonResponse
    {
        return ApiResponse::success(UserProfileResource::make(Auth::user()));
    }

    /**
     * 更新用户信息
     *
     * @param  UpdateUserProfileRequest  $request  用户信息请求
     *
     * @return JsonResponse 更新后的用户信息
     */
    public function update(UpdateUserProfileRequest $request): JsonResponse
    {
        $data = $request->safe()->only(['nickname', 'gender', 'birthday', 'avatar']);
        $data = array_filter($data, static fn ($item) => !blank($item));

        if (isset($data['avatar'])) {
            $data['avatar'] = $this->extractStoragePath($data['avatar']);
        }

        $user = Auth::user();
        $user->profile->update($data);

        return ApiResponse::success(UserProfileResource::make($user), '用户信息更新成功');
    }

    /**
     * 从URL中提取存储路径
     */
    protected function extractStoragePath(string $value): string
    {
        if (preg_match('#/storage/(.+)$#', $value, $matches)) {
            return $matches[1];
        }

        return $value;
    }
}
