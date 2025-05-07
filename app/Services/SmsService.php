<?php

namespace App\Services;

use App\Enums\SmsChannel;
use App\Models\SmsCode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\InvalidArgumentException;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;

class SmsService
{
    /**
     * @throws InvalidArgumentException
     */
    public function sendCode(string $phone, SmsChannel $channel): string
    {
        SmsCode::where('phone', $phone)
            ->where('channel', $channel)
            ->delete();

        try {
            $easySms = app(EasySms::class);
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

            return $code;
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage());
        } catch (NoGatewayAvailableException) {
            throw new InvalidArgumentException('没有可用的短信网关');
        }
    }

    protected function generateCode(): string
    {
        $length = config('easy-sms.length');
        if (config('easy-sms.debug')) {
            return Str::repeat('0', config('easy-sms.length'));
        }

        $max = pow(10, $length) - 1;

        return Str::padLeft(rand(0, $max), $length, '0');
    }

    public function verifyCode(string $phone, string $code): bool
    {
        $record = SmsCode::where('phone', $phone)
            ->where('code', $code)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($record) {
            $record->delete();

            return true;
        }

        return false;
    }
}
