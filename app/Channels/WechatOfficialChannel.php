<?php

namespace App\Channels;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\WechatOfficialMessage;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;
use Overtrue\LaravelWeChat\EasyWeChat;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * composer require overtrue/laravel-wechat
 * 微信公众号通知通道
 *
 * 依赖包：
 * - overtrue/laravel-wechat：Laravel 封装，项目已安装（v8），底层依赖 w7corp/easywechat v6。
 *
 * 实现过程：
 * 1. 配置：config/easywechat.php 的 official_account 段落已就绪，
 *    对应 .env 的 WECHAT_OFFICIAL_ACCOUNT_APPID、WECHAT_OFFICIAL_ACCOUNT_SECRET、
 *    WECHAT_OFFICIAL_ACCOUNT_TOKEN、WECHAT_OFFICIAL_ACCOUNT_AES_KEY。
 *    与小程序同理，生产上公众号密钥是租户级的（wechats 表），
 *    多租户场景应参考 MiniProgramController 动态构造 Application 实例，
 *    EasyWeChat::officialAccount() 只适合全局单应用。
 * 2. access_token 由 EasyWeChat 自动获取并缓存（依赖 Laravel Cache），
 *    无需自己维护，但需保证生产环境缓存驱动可用（项目为 Redis）。
 * 3. 发送模板消息：POST /cgi-bin/message/template/send
 *    请求体 {"touser": "{openid}", "template_id": "...", "url": "...",
 *           "miniprogram": {"appid": "...", "pagepath": "..."},
 *           "data": {"User": {"value": "Jason"}}, "client_msg_id": "..."}
 *    data 的键是模板占位符名（如 first、keyword1、remark），不是固定字段。
 * 4. 跳转二选一：url 走公众号网页，miniprogram 走小程序页面；
 *    两者同时传时微信以 miniprogram 优先，但 miniprogram 为空数组会报错
 *    （详见下方注意事项）。
 * 5. openid 来源：公众号与用户需存在绑定关系才能下发模板消息，
 *    即 socialites 表 provider = 'WeChat' 的 provider_id（见 SendRedpackJob::getWechatOpenid）。
 * 6. 常见错误码：40003 openid 无效、40037 template_id 无效、
 *    43004 接收者未关注公众号、45047 模板消息接口调用超限。
 *
 * 注意事项（当前实现的待改进点）：
 * - client_msg_id 建议用唯一值（Str::uuid()），用 time() 在同一秒内会重复，
 *   微信侧以此做去重，可能导致消息被丢弃。
 * - miniprogram 未设置时传空数组 [] 微信会报参数错误，
 *   应仅在设置了小程序跳转时携带该字段。
 * - 模板消息仅服务号可用，且要求用户与公众号有过交互（关注/授权）。
 */
class WechatOfficialChannel
{
    /**
     * 发送公众号模板消息
     *
     * @param  Authenticatable  $user  通知用户
     * @param  Notification  $notification  通知内容
     *
     * @throws InvalidArgumentException 通知不支持公众号发送
     * @throws TransportExceptionInterface 微信接口请求失败
     */
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
        } else {
            throw new InvalidArgumentException('The notification is not supported.');
        }
    }
}
