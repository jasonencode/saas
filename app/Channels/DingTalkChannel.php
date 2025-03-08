<?php

namespace App\Channels;

use App\Contracts\Authenticatable;
use Illuminate\Notifications\Notification;

/**
 * 钉钉通知通道
 */
class DingTalkChannel
{
    public function send(Authenticatable $user, Notification $notification): void
    {
        if (method_exists($notification, 'toDingTalk')) {
            $message = $notification->toDingTalk($user);
        }
    }
}
