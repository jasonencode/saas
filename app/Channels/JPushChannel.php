<?php

namespace App\Channels;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\JPushMessage;
use Illuminate\Notifications\Notification;

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
        }
    }
}
