<?php

namespace App\Channels;

use App\Contracts\Authenticatable;
use App\Services\Foundation\SmsService;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;

/**
 * composer require overtrue/easy-sms
 * 短信通知通道
 *
 * 依赖包：
 * - overtrue/easy-sms：多网关短信 SDK，项目已安装并在 config/easy-sms.php 配置，
 *   默认网关为 debug + aliyun（OrderStrategy 顺序调用，前一个失败自动降级）。
 *
 * 实现过程：
 * 1. 发送逻辑复用 App\Services\Foundation\SmsService::send()，
 *    通道内只做「解析手机号 + 交给服务发送」，不重复构建 EasySms 实例。
 * 2. 手机号来源：users 表没有独立的 phone 字段，手机号即 users.username
 *    （见 MiniProgramController 中 'username' => $phoneNumber 的写法）。
 * 3. 消息内容由 toSms() 返回，返回结构需与 EasySms::send() 的 message 参数一致：
 *    ['content' => '全文内容', 'template' => '模板名称', 'data' => ['code' => '1234']]。
 *    阿里云等网关要求模板必须已在服务商后台报备，template 填模板名称而非模板 ID。
 * 4. 发送失败：EasySms 抛出 Overtrue\EasySms\Exceptions\NoGatewayAvailableException，
 *    交给队列重试（BaseNotification::$tries = 3、$backoff = [10, 60, 300]）。
 * 5. 调试：config/easy-sms.php 的 debug 开关打开时不真正发短信，
 *    DebugGateway 直接返回固定验证码，本地开发无需配置真实密钥。
 * 6. 限流：发送验证码的接口已挂 sms 限流器（见 AppServiceProvider），
 *    通知类短信建议在业务侧另外控制频率，避免触发运营商风控。
 */
class SmsChannel
{
    public function __construct(protected SmsService $smsService) {}

    /**
     * 发送短信通知
     *
     * @param  Authenticatable  $user  通知用户
     * @param  Notification  $notification  通知内容
     *
     * @throws InvalidArgumentException 通知不支持短信发送或用户未绑定手机号
     * @throws NoGatewayAvailableException 所有短信网关均发送失败
     */
    public function send(Authenticatable $user, Notification $notification): void
    {
        if (!method_exists($notification, 'toSms')) {
            throw new InvalidArgumentException('The notification is not supported.');
        }

        /** @var array{content?: string, template?: string, data?: array<string, mixed>}|null $message */
        $message = $notification->toSms($user);

        if (!$message) {
            return;
        }

        $this->smsService->send($this->getPhone($user), $message);
    }

    /**
     * 获取接收手机号
     *
     * @param  Authenticatable  $user  通知用户
     *
     * @throws InvalidArgumentException 用户未绑定手机号
     *
     * @return string 手机号
     */
    protected function getPhone(Authenticatable $user): string
    {
        $phone = (string) $user->username;

        if ($phone === '') {
            throw new InvalidArgumentException('The user has no phone number.');
        }

        return $phone;
    }
}
