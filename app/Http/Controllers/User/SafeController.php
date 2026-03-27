<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SafeController extends Controller
{
    /**
     * 修改密码
     */
    public function password(UpdatePasswordRequest $request): JsonResponse
    {
        Auth::user()->update([
            'password' => $request->post('new_pass'),
        ]);

        return ApiResponse::noContent('密码修改成功');
    }

    public function records(): JsonResponse
    {
        $list = Auth::user()->records()->latest()->paginate();

        return ApiResponse::success($list);
    }

    public function logout(): JsonResponse
    {
        Auth::user()->currentAccessToken()?->delete();

        return ApiResponse::noContent('已退出登录');
    }
}
