<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\User\UserProfileResource;
use App\Http\Responses\ApiResponse;
use App\Models\User\User;
use App\Support\TenantResolver\TenantResolver;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    /**
     * 用户注册
     *
     * @param  RegisterRequest  $request  注册请求
     *
     * @return JsonResponse 注册结果
     */
    public function index(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'tenant_id' => TenantResolver::current()->id,
            'username' => $request->safe()->string('username'),
            'password' => $request->safe()->string('password'),
        ]);

        return ApiResponse::created(UserProfileResource::make($user), '用户注册成功');
    }
}
