<?php

namespace App\Http\Controllers\Auth;

use App\Enums\SmsChannel;
use App\Http\Controllers\AuthController;
use App\Http\Requests\SmsSendRequest;
use App\Services\SmsService;

class SmsController extends AuthController
{
    public function send(SmsSendRequest $request)
    {
        $phone = $request->safe()->string('phone');

        $code = resolve(SmsService::class)->sendCode($phone, SmsChannel::Login);

        return $this->success(['code' => $code]);
    }
}
