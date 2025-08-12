<?php

namespace App\Channels;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\WechatOfficialMessage;
use Illuminate\Notifications\Notification;
use Overtrue\LaravelWeChat\EasyWeChat;

/**
 * composer require overtrue/laravel-wechat
 * 微信公众号通知通道
 */
class WechatOfficialChannel
{
    public function send(Authenticatable $user, Notification $notification): void
    {
        if (method_exists($notification, 'toWechatOfficial')) {
            /** @var WechatOfficialMessage $message */
            $message = $notification->toWechatOfficial($user);

            if (!$message) {
                return;
            }

            $app = EasyWeChat::officialAccount();

            $app->getClient()->postJson('cgi-bin/message/template/send', [
                'touser' => $message->openId,
                'template_id' => $message->template_id,
                'url' => $message->url,
                'miniprogram' => $message->miniprogram,
                'data' => $message->data,
                'client_msg_id' => time(),
            ]);
        }
    }
}
