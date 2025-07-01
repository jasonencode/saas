<?php

namespace App\Channels;

use App\Contracts\Authenticatable;
use App\Contracts\WechatOfficialMessage;
use Illuminate\Notifications\Notification;
use Overtrue\LaravelWeChat\EasyWeChat;

/**
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
                'touser' => $message->getOpenId(),
                'template_id' => $message->getTemplateId(),
                'url' => $message->getUrl(),
                'miniprogram' => $message->getMiniprogram(),
                'data' => $message->getData(),
                'client_msg_id' => time(),
            ]);
        }
    }
}
