<?php

namespace App\Services;

use App\Contracts\ServiceInterface;
use App\Enums\SmsChannel;
use App\Extensions\SmsGateways\DebugGateway;
use App\Models\SmsCode;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Overtrue\EasySms\EasySms;

class SmsService implements ServiceInterface
{
    public function sendCode(string $phone, SmsChannel $channel): string
    {
        SmsCode::where('phone', $phone)
            ->where('channel', $channel)
            ->delete();

        try {
            $config = config('easy-sms');
            $easySms = new EasySms($config);

            // 注册
            $easySms->extend('debug', function ($gatewayConfig) {
                return new DebugGateway($gatewayConfig);
            });

            $code = $this->generateCode();
            $result = $easySms->send($phone, [
                'content' => '您的验证码为: '.$code,
                'template' => $channel->getTemplate(),
                'data' => [
                    'code' => $code,
                ],
            ]);
            SmsCode::create([
                'phone' => $phone,
                'channel' => $channel,
                'gateway' => array_key_first($result),
                'code' => $code,
                'used' => false,
                'expires_at' => Carbon::now()->addMinutes(5),
            ]);

            return true;
        } catch (Exception) {
            return false;
        }
    }

    private function generateCode(): string
    {
        if (config('easy-sms.debug')) {
            return config('easy-sms.gateways.debug.code');
        }

        $length = config('easy-sms.length');
        $max = (10 ** $length) - 1;

        return Str::padLeft(random_int(0, $max), $length, '0');
    }

    /**
     * 校验验证码
     *
     * @param  string  $phone
     * @param  string  $code
     * @return bool
     */
    public function verifyCode(string $phone, string $code): bool
    {
        $sms = SmsCode::where('phone', $phone)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($sms && !$sms->used && $sms->code === $code) {
            $sms->update(['used' => true]);

            return true;
        }

        return false;
    }
}
