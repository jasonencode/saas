<?php

namespace App\Http\Controllers\Auth;

use App\Factories\AuthResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\PasswordLoginRequest;
use App\Models\Tenant;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * 使用账户密码登录
     *
     * @param  PasswordLoginRequest  $request
     * @return JsonResponse
     */
    public function password(PasswordLoginRequest $request): JsonResponse
    {
        $credentials = $request->safe()->only(['username', 'password']);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            return $this->success(new AuthResponse($user));
        }

        return $this->error('账号或密码错误', 422);
    }

    /**
     * 租户获取API授权
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function tenant(Request $request): JsonResponse
    {
        $app_key = $request->post('app_key');
        $app_secret = $request->post('app_secret');

        $tenant = Tenant::where('app_key', $app_key)->first();

        if (!$tenant || $tenant->app_secret !== $app_secret) {
            return $this->error('app_key or app_secret authentication failed');
        }

        event(new Login('sanctum', $tenant, false));

        return $this->success([
            'access_token' => base64_encode($tenant->createToken('Tenant', ['*'],
                Carbon::now()->addHours(2))->plainTextToken),
        ]);
    }
}