<?php

namespace App\Services\Foundation;

use App\Contracts\ServiceInterface;
use App\Enums\User\SmsChannel;
use App\Models\User\SmsCode;
use App\Support\SmsGateways\DebugGateway;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Overtrue\EasySms\EasySms;
use Random\RandomException;

class SmsService implements ServiceInterface
{
    /**
     * 发送验证码
     *
     * @param  string  $phone  手机号
     * @param  SmsChannel  $channel  短信渠道
     *
     * @return bool 是否发送成功
     */
    public function sendCode(string $phone, SmsChannel $channel): bool
    {
        SmsCode::where('phone', $phone)
            ->where('channel', $channel)
            ->delete();

        try {
            $config = config('easy-sms');
            $easySms = new EasySms($config);

            // 注册
            $easySms->extend('debug', function (array $gatewayConfig) {
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
        } catch (Exception $e) {
            report($e);

            return false;
        }
    }

    /**
     * 生成验证码
     *
     *
     * @throws RandomException 随机数生成异常
     *
     * @return string 验证码
     */
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
     * @param  string  $phone  手机号
     * @param  string  $code  验证码
     *
     * @return bool 是否验证成功
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
