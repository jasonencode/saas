<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Jason\Captcha\Facades\Captcha;

class CaptchaController extends Controller
{
    /**
     * 获取验证码
     *
     * @return JsonResponse 验证码图片和密钥
     */
    public function index(): JsonResponse
    {
        $res = Captcha::create('default', true);

        return ApiResponse::success([
            'key' => $res['key'],
            'img' => $res['img'],
        ]);
    }
}
