<?php

namespace App\Http\Controllers\Auth;

use App\Extensions\TenantResolver\TenantResolver;
use App\Factories\AuthResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MiniProgramLoginRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Foundation\WechatMini;
use App\Models\User\User;
use EasyWeChat\Kernel\Exceptions\HttpException;
use EasyWeChat\MiniApp\Application;
use Illuminate\Http\JsonResponse;

class MiniProgramController extends Controller
{
    /**
     * 微信小程序手机号登录
     */
    public function phone(MiniProgramLoginRequest $request): JsonResponse
    {
        $code = $request->validated('code');

        $tenant = TenantResolver::current();

        // 获取租户的微信小程序配置
        $wechatMini = WechatMini::ofTenant($tenant->id)
            ->first();

        if (!$wechatMini) {
            return ApiResponse::error('租户未配置微信小程序', 400);
        }

        $miniApp = new Application([
            'app_id' => $wechatMini->app_id,
            'secret' => $wechatMini->app_secret,
        ]);

        try {
            $phoneInfo = $miniApp->getUtils()->getPhoneNumber($code);
        } catch (HttpException $e) {
            return ApiResponse::error('微信登录失败：'.$e->getMessage(), 400);
        }

        $phoneNumber = $phoneInfo['phone_info']['purePhoneNumber'];

        // 按手机号查找或创建用户
        $user = User::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'username' => $phoneNumber,
            ],
            [
                'password' => null,
            ]
        );

        return ApiResponse::success(new AuthResponse($user), '登录成功');
    }
}
