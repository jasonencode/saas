<?php

namespace App\Channels;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\WechatMiniMessage;
use EasyWeChat\Factory;
use EasyWeChat\MiniApp\Application;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;

/**
 * 小程序消息通道
 */
class WechatMiniChannel
{
    /**
     * 发送消息
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

            $result = $miniApp->subscribe_message->send([
                'touser' => $message->getToUser(),
                'template_id' => $message->getTemplateId(),
                'page' => $message->getPage(),
                'data' => $message->getData(),
            ]);
        } catch (\Exception $e) {
            // 可以选择是否抛出异常，这里选择记录日志后继续执行
        }
    }

    /**
     * 获取小程序实例
     *
     * @return Application
     */
    protected function getMiniApp()
    {
        $config = config('easywechat.mini_app.default');

        if (empty($config['app_id']) || empty($config['secret'])) {
            throw new \RuntimeException('Wechat mini app configuration is missing');
        }

        return Factory::miniApp($config);
    }
}
