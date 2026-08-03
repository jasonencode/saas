<?php

namespace App\Channels;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\DingTalkMessage;
use Illuminate\Notifications\Notification;

/**
 * 钉钉通知通道
 */
class DingTalkChannel
{
    /**
     * 发送钉钉通知
     *
     * @param  Authenticatable  $user  通知用户
     * @param  Notification  $notification  通知内容
     */
    public function send(Authenticatable $user, Notification $notification): void
    {
        if (method_exists($notification, 'toDingTalk')) {
            /** @var DingTalkMessage $message */
            $message = $notification->toDingTalk($user);
        }
    }
}
