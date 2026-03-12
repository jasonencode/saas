<?php

namespace App\Channels;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\WechatMiniMessage;
use Illuminate\Notifications\Notification;

/**
 * 小程序消息通道
 */
class WechatMiniChannel
{
    public function send(Authenticatable $user, Notification $notification): void
    {
        if (method_exists($notification, 'toWechatMini')) {
            /** @var WechatMiniMessage $message */
            $message = $notification->toWechatMini($user);
        }
    }
}
