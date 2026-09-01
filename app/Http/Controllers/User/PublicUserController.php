<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\PublicUserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User\User;
use Illuminate\Http\JsonResponse;

class PublicUserController extends Controller
{
    /**
     * 获取指定用户公开信息
     *
     * @param  User  $user  用户
     *
     * @return JsonResponse 用户公开信息
     */
    public function show(User $user): JsonResponse
    {
        return ApiResponse::success(PublicUserResource::make($user));
    }
}
