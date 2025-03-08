<?php

namespace App\Extensions\SmsGateways;

use InvalidArgumentException;
use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Gateways\Gateway;
use Overtrue\EasySms\Support\Config;

class DebugGateway extends Gateway
{
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
