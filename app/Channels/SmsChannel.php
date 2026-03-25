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
    public function send(Authenticatable $user, Notification $notification): void
    {
        if (method_exists($notification, 'toSms')) {
            $message = $notification->toSms($user);
        } else {
            throw new InvalidArgumentException('The notification is not supported.');
        }
    }
}
