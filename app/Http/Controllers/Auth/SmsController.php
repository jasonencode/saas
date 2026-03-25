<?php

namespace App\Http\Controllers\Auth;

use App\Enums\SmsChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\SmsSendRequest;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SmsController extends Controller
{
    public function send(SmsSendRequest $request): JsonResponse
    {
        $phone = $request->safe()->string('phone');

        if (service(SmsService::class)->sendCode($phone, SmsChannel::Login)) {
            return $this->success('', Response::HTTP_CREATED);
        }

        return $this->error('短信验证码发送失败');
    }
}
