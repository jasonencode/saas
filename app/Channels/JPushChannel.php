<?php

namespace App\Channels;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\JPushMessage;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;

/**
 * 极光推送通道
 */
class JPushChannel
{
    public function send(Authenticatable $user, Notification $notification): void
    {
        if (method_exists($notification, 'toJPush')) {
            /** @var JPushMessage $message */
            $message = $notification->toJPush($user);
        } else {
            throw new InvalidArgumentException('The notification is not supported.');
        }
    }
}
