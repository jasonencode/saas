<?php

namespace App\Http\Controllers\Auth;

use App\Extensions\TenantResolver\TenantResolver;
use App\Http\Controllers\Controller;
use App\Http\Resources\Users\UserProfileResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class RegisterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $user = User::create([
                'tenant_id' => TenantResolver::current()->id,
                'username' => $request->post('username'),
                'password' => $request->post('password'),
            ]);

            return ApiResponse::created(UserProfileResource::make($user), '用户注册成功');
        } catch (Throwable) {
            return ApiResponse::error('用户注册失败', 400);
        }
    }
}
