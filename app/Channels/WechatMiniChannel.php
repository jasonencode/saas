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
 * composer require overtrue/laravel-wechat
 * 小程序消息通道
 *
 * 依赖包：
 * - overtrue/laravel-wechat：Laravel 封装，项目已安装（v8），底层依赖 w7corp/easywechat v6。
 *
 * 实现过程：
 * 1. 配置：config/easywechat.php 中的 mini_app 段落目前是注释状态，需要放开并补上
 *    WECHAT_MINI_APP_APPID、WECHAT_MINI_APP_SECRET，否则 getMiniApp() 会抛
 *    RuntimeException。
 *    注意：生产上小程序密钥是租户级的，保存在 wechat_minis 表
 *    （见 App\Models\Foundation\WechatMini），参考 MiniProgramController 的写法
 *    new Application(['app_id' => ..., 'secret' => ...]) 动态构造，
 *    比读全局配置更符合多租户架构；EasyWeChat::miniApp() 仅适合单应用场景。
 * 2. 实例化：new EasyWeChat\MiniApp\Application($config)，
 *    配置齐全后可换成容器单例（EasyWeChat::miniApp()）避免每次重复构造。
 * 3. 发送订阅消息：POST /cgi-bin/message/subscribe/send
 *    请求体 {"touser": "{openid}", "template_id": "...", "page": "pages/index/index",
 *           "data": {"thing1": {"value": "..."}, "time2": {"value": "..."}}}
 *    注意 data 的每个键必须是「模板字段名 + 序号」（如 thing1、time2、amount3），
 *    且 value 长度受字段类型限制（thing 20 字符以内），超长会报 47003。
 * 4. 前置条件：一次性订阅消息需要用户在小程序内主动授权，
 *    每次授权只能发送一条，服务端必须记录订阅配额
 *    （建议新增 subscribe_quotas 表按 openid + template_id 计数，发送后 -1）。
 *    长期订阅仅对特定行业类目开放。
 * 5. openid 来源：socialites 表 provider = 'WeChat' 且 provider_id 为小程序 openid。
 *    若公众号与小程序绑定同一开放平台，可用 union_id 做关联。
 * 6. 常见错误码：43101 用户未订阅/已拒绝、47003 参数格式错误、
 *    40037 template_id 无效、40003 openid 无效，需要记录日志便于排查。
 * 7. 发送失败时 report() 后原样抛出，交由队列重试
 *    （BaseNotification::$tries = 3、$backoff = [10, 60, 300]）。
 *    注意 43101 属于业务上的正常情况（用户没订阅），重试无意义，
 *    若日志中大量出现，应改为按错误码跳过抛出。
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
     * @throws Exception 微信接口调用失败
     */
    public function send(Authenticatable $user, Notification $notification): void
    {
        if (!method_exists($notification, 'toWechatMini')) {
            throw new InvalidArgumentException('The notification is not supported.');
        }

        /** @var WechatMiniMessage|null $message */
        $message = $notification->toWechatMini($user);

        if (!$message) {
            return;
        }

        try {
            $this->getMiniApp()->getClient()->post('/cgi-bin/message/subscribe/send', [
                'touser' => $message->getToUser(),
                'template_id' => $message->getTemplateId(),
                'page' => $message->getPage(),
                'data' => $message->getData(),
            ]);
        } catch (Exception $e) {
            report($e);

            throw $e;
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
