<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CaptchaCreateRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Jason\Captcha\Facades\Captcha;

class CaptchaController extends Controller
{
    /**
     * 获取验证码
     *
     * @param  CaptchaCreateRequest  $request  验证码请求
     *
     * @return JsonResponse 验证码图片、密钥和类型
     */
    public function index(CaptchaCreateRequest $request): JsonResponse
    {
        $type = $request->captchaType();
        $res = Captcha::create($type, true);

        return ApiResponse::success([
            'type' => $type,
            'key' => $res['key'],
            'img' => $res['img'],
        ]);
    }
}
