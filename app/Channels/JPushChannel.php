<?php

namespace App\Channels;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\JPushMessage;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;
use JPush\Client;
use JPush\Exceptions\JPushException;
use JPush\PushPayload;
use RuntimeException;

/**
 * composer require jpush/jpush
 * 极光推送通道
 *
 * 依赖包：
 * - jpush/jpush：极光官方 PHP SDK（当前 v3.7.0，命名空间 JPush\，仅需 ext-curl）
 *   注意该 SDK 代码风格较老（构造函数无类型声明），但接口稳定，无需自行封装 REST。
 *
 * 实现过程：
 * 1. 配置：config/services.php 的 jpush 段落，对应 .env 的
 *    JPUSH_APP_KEY、JPUSH_MASTER_SECRET、JPUSH_APNS_PRODUCTION。
 * 2. 实例化客户端：new JPush\Client($appKey, $masterSecret, $logFile)，
 *    完整签名为 __construct($appKey, $masterSecret, $logFile, $retryTimes, $zone)，
 *    后两个参数可省略；日志文件指向 storage/logs/jpush.log。
 * 3. 构建并发送推送：
 *    $client->push()
 *        ->setPlatform(['android', 'ios'])            // 或传 'all'
 *        ->addAlias($aliases)                         // 别名推送，默认取用户 ID
 *        ->setNotificationAlert($alert)               // 全平台默认提示文案
 *        ->androidNotification($alert, ['title' => ..., 'extras' => [...]])
 *        ->iosNotification($alert, ['sound' => 'default', 'extras' => [...]])
 *        ->options(['apns_production' => true])       // 生产环境必须为 true，否则 iOS 收不到
 *        ->send();
 * 4. 注意 options() 在未显式传入 apns_production 时会强制置为 false，
 *    因此通道里始终按配置注入，避免线上误走 APNs 开发环境。
 * 5. 别名绑定：客户端 SDK 调用 setAlias 上报后，服务端才能按别名推送。
 *    本通道默认使用用户主键作为别名，客户端 setAlias 时需保持一致。
 *    一个用户多设备时客户端会对同一别名重复 setAlias，极光会自动维护多设备关系。
 * 6. 消息内容由 toJPush() 返回的 JPushMessage 构建，通道只负责发送。
 * 7. 异常：SDK 抛出 JPush\Exceptions\JPushException（含 APIConnectionException、
 *    APIRequestException），通道内 report() 后继续抛出，交由队列重试
 *    （BaseNotification::$tries = 3、$backoff = [10, 60, 300]）。
 * 8. 频率限制：极光对同一 AppKey 的推送接口有 QPS 限制，超限返回 429，
 *    大批量推送前应确认是否需要限速或改用定时任务（$client->schedule()）。
 *
 * @see https://docs.jiguang.cn/jpush/server/push/server_overview
 */
class JPushChannel
{
    /**
     * 发送极光推送通知
     *
     * @param  Authenticatable  $user  通知用户
     * @param  Notification  $notification  通知内容
     *
     * @throws InvalidArgumentException 通知不支持极光推送或推送内容为空
     * @throws RuntimeException 极光推送配置缺失
     * @throws JPushException 极光推送接口调用失败
     */
    public function send(Authenticatable $user, Notification $notification): void
    {
        if (!method_exists($notification, 'toJPush')) {
            throw new InvalidArgumentException('The notification is not supported.');
        }

        /** @var JPushMessage|null $message */
        $message = $notification->toJPush($user);

        if (!$message) {
            return;
        }

        $alert = $message->getAlert();

        if ($alert === '') {
            throw new InvalidArgumentException('The JPush alert can not be empty.');
        }

        $extras = $message->getExtras();
        $title = $message->getTitle() ?? config('app.name');

        try {
            $pusher = $this->makeClient()->push()
                ->setPlatform($message->getPlatforms())
                ->setNotificationAlert($alert);

            $this->applyAudience($pusher, $user, $message);

            $pusher->androidNotification($alert, ['title' => $title, 'extras' => $extras])
                ->iosNotification($alert, ['sound' => 'default', 'extras' => $extras])
                ->options($this->getOptions($message))
                ->send();
        } catch (JPushException $e) {
            report($e);

            throw $e;
        }
    }

    /**
     * 设置推送目标
     *
     * 设备标识优先；未设置时按别名推送，别名缺省为当前用户主键
     * （客户端 SDK setAlias 时需使用同一值）。
     *
     * 注意 SDK 的 addAlias() 与 addAllAudience() 互斥，
     * 且未设置任何 audience 时 build() 会抛异常，因此必须始终落到一个目标上。
     *
     * @param  PushPayload  $pusher  推送实例
     * @param  Authenticatable  $user  通知用户
     * @param  JPushMessage  $message  推送消息
     */
    protected function applyAudience(PushPayload $pusher, Authenticatable $user, JPushMessage $message): void
    {
        if ($registrationIds = $message->getRegistrationIds()) {
            $pusher->addRegistrationId($registrationIds);

            return;
        }

        $pusher->addAlias($message->getAliases() ?: [(string) $user->getKey()]);
    }

    /**
     * 获取推送可选参数
     *
     * options() 未显式设置 apns_production 时会强制置为 false，
     * 因此这里始终按配置注入，避免生产环境误走 APNs 开发环境。
     *
     * @param  JPushMessage  $message  推送消息
     *
     * @return array<string, mixed> 推送可选参数
     */
    protected function getOptions(JPushMessage $message): array
    {
        return array_merge([
            'apns_production' => (bool) config('services.jpush.apns_production'),
        ], $message->getOptions());
    }

    /**
     * 创建极光客户端
     *
     * @throws RuntimeException 极光推送配置缺失
     *
     * @return Client 极光客户端
     */
    protected function makeClient(): Client
    {
        $appKey = (string) config('services.jpush.app_key');
        $masterSecret = (string) config('services.jpush.master_secret');

        if ($appKey === '' || $masterSecret === '') {
            throw new RuntimeException('极光推送配置缺失，请检查 JPUSH_APP_KEY 与 JPUSH_MASTER_SECRET');
        }

        return new Client($appKey, $masterSecret, storage_path('logs/jpush.log'));
    }
}
