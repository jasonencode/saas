<?php

namespace App\Channels;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\WechatMiniMessage;
use EasyWeChat\MiniApp\Application;
use Exception;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;
use RuntimeException;

/**
 * 小程序消息通道
 */
class WechatMiniChannel
{
    /**
     * 发送小程序订阅消息
     *
     * @param  Authenticatable  $user  通知用户
     * @param  Notification  $notification  通知内容
     *
     * @throws InvalidArgumentException 通知不支持小程序发送
     * @throws RuntimeException 小程序配置缺失
     */
    public function send(Authenticatable $user, Notification $notification): void
    {
        if (method_exists($notification, 'toWechatMini')) {
            /** @var WechatMiniMessage $message */
            $message = $notification->toWechatMini($user);
        } else {
            throw new InvalidArgumentException('The notification is not supported.');
        }

        try {
            $miniApp = $this->getMiniApp();

            $miniApp->getClient()->post('/cgi-bin/message/subscribe/send', [
                'touser' => $message->getToUser(),
                'template_id' => $message->getTemplateId(),
                'page' => $message->getPage(),
                'data' => $message->getData(),
            ]);
        } catch (Exception $e) {
            // 可以选择是否抛出异常，这里选择记录日志后继续执行
        }
    }

    /**
     * 获取小程序实例
     *
     * @throws RuntimeException 小程序配置缺失
     *
     * @return Application 小程序实例
     */
    protected function getMiniApp(): Application
    {
        $config = config('easywechat.mini_app.default');

        if (empty($config['app_id']) || empty($config['secret'])) {
            throw new RuntimeException('Wechat mini app configuration is missing');
        }

        return new Application($config);
    }
}
