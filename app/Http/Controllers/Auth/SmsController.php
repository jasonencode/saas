<?php

namespace App\Http\Controllers\Auth;

use App\Enums\User\SmsChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SmsSendRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Foundation\SmsService;
use Illuminate\Http\JsonResponse;

class SmsController extends Controller
{
    /**
     * 发送短信验证码
     *
     * @param  SmsSendRequest  $request  短信发送请求
     *
     * @return JsonResponse 发送结果
     */
    public function send(SmsSendRequest $request): JsonResponse
    {
        $phone = $request->safe()->string('phone');

        if (service(SmsService::class)->sendCode($phone, SmsChannel::Login)) {
            return ApiResponse::noContent();
        }

        return ApiResponse::error('短信验证码发送失败');
    }
}
