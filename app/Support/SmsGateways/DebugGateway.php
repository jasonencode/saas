<?php

namespace App\Support\SmsGateways;

use InvalidArgumentException;
use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Gateways\Gateway;
use Overtrue\EasySms\Support\Config;

/**
 * 调试短信网关
 *
 * 仅在调试模式下生效，返回发送内容而不实际发送。
 */
class DebugGateway extends Gateway
{
    /**
     * 发送短信（调试模式）
     *
     * @param  PhoneNumberInterface  $to  接收手机号
     * @param  MessageInterface  $message  短信内容
     * @param  Config  $config  配置选项
     *
     * @throws InvalidArgumentException 非调试模式
     *
     * @return array 发送结果
     */
    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config): array
    {
        if (!config('easy-sms.debug')) {
            throw new InvalidArgumentException('非调试模式');
        }

        return [
            'to' => $to->getNumber(),
            'content' => $message->getContent(),
            'template' => $message->getTemplate(),
            'data' => $message->getData(),
        ];
    }
}
