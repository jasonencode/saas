<?php

namespace App\Channels;

use App\Contracts\Authenticatable;
use Illuminate\Notifications\Notification;

/**
 * 微信公众号通知通道
 */
class WechatOfficialChannel
{
    public function send(Authenticatable $user, Notification $notification): void
    {
        if (method_exists($notification, 'toWechatOfficial')) {
            $message = $notification->toWechatOfficial($user);
        }
    }
}
