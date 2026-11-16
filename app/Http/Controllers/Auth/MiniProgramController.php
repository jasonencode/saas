<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MiniProgramLoginRequest;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\AuthResponse;
use App\Models\Foundation\WechatMini;
use App\Models\User\User;
use EasyWeChat\Kernel\Exceptions\HttpException;
use EasyWeChat\MiniApp\Application;
use Illuminate\Http\JsonResponse;

class MiniProgramController extends Controller
{
    /**
     * 微信小程序手机号登录
     *
     * 账号体系：用户名由 openid 派生（mp_{openid}），手机号不作为用户名创建账号，
     * 避免已注册手机号被小程序直接登录（抢注）；手机号仅用于匹配存量账号，
     * 命中后登录原账号。openid 通过 wx.login 的 login_code 换取。
     *
     * @param  MiniProgramLoginRequest  $request  小程序登录请求
     *
     * @return JsonResponse 登录结果
     */
    public function phone(MiniProgramLoginRequest $request): JsonResponse
    {
        $code = $request->validated('code');
        $loginCode = $request->validated('login_code');

        $wechatMini = WechatMini::first();

        if (!$wechatMini) {
            return ApiResponse::error('租户未配置微信小程序');
        }

        $miniApp = new Application($wechatMini->getConfig());

        try {
            // wx.login 凭证换取 openid（用户在该小程序下的唯一标识）
            $session = $miniApp->getUtils()->codeToSession($loginCode);
            $phoneInfo = $miniApp->getUtils()->getPhoneNumber($code);
        } catch (HttpException $e) {
            return ApiResponse::error('微信登录失败');
        }

        $openid = $session['openid'];
        $phoneNumber = $phoneInfo['phone_info']['purePhoneNumber'];

        // 手机号仅匹配存量账号（历史手机号注册用户）；未命中则按 openid 创建，
        // username 唯一约束即 (provider, openid) 唯一键，杜绝手机号抢注
        $user = User::query()->where('username', $phoneNumber)->first()
            ?? User::firstOrCreate(
                ['username' => 'mp_'.$openid],
                ['password' => null],
            );

        return ApiResponse::success(AuthResponse::make($user), '登录成功');
    }
}
