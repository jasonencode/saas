<?php

namespace App\Channels;

use App\Contracts\Authenticatable;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;

/**
 * 短信通知通道
 */
class SmsChannel
{
    /**
     * 发送短信通知
     *
     * @param  Authenticatable  $user  通知用户
     * @param  Notification  $notification  通知内容
     *
     * @throws InvalidArgumentException 通知不支持短信发送
     */
    public function send(Authenticatable $user, Notification $notification): void
    {
        if (method_exists($notification, 'toSms')) {
            $message = $notification->toSms($user);
        } else {
            throw new InvalidArgumentException('The notification is not supported.');
        }
    }
}
