<?php

namespace App\Channels;

use App\Contracts\Authenticatable;
use Illuminate\Notifications\Notification;

/**
 * 极光推送通道
 */
class JPushChannel
{
    public function send(Authenticatable $user, Notification $notification): void
    {
        if (method_exists($notification, 'toJPush')) {
            $message = $notification->toJPush($user);
        }
    }
}
