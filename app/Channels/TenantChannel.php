<?php

namespace App\Channels;

use App\Models\System\Tenant;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;

/**
 * 租户通知通道
 *
 * 依赖包：无，使用框架自带的 filament/filament 与 laravel/framework 通知组件。
 *
 * 实现过程：
 * 1. 该通道的 notifiable 是 Tenant 而非用户，因此不走 Laravel 的
 *    ChannelManager（Laravel 只按 notifiable 派发），而是由通知类的 via() 返回
 *    TenantChannel::class，再由业务侧显式指定目标租户并调用 send()。
 * 2. 消息内容由 toTenant() 返回 Filament\Notifications\Notification 实例
 *    （注意不是 Illuminate 的 Notification），便于直接复用 Filament 的
 *    title/body/icon/color/actions 语法与前端渲染。
 * 3. 落库：$notify->sendToDatabase($tenant->administrators) 会写入 notifications 表，
 *    administrators 关联见 App\Models\System\Tenant::administrators()。
 * 4. 当前实现是「发给租户下全部管理员」，租户管理员较多时注意：
 *    sendToDatabase() 会为每个接收者插入一行，建议按 chunk() 分批，
 *    或改为写入租户级的汇总通知后再由管理员拉取。
 * 5. 若需要实时提醒，可在 sendToDatabase 之外再调用 $notify->broadcast()，
 *    需要额外配置广播驱动（项目当前未启用）。
 * 6. 该通道依赖 Tenant 模型，因此不适合放在 Authenticatable 的 via() 中；
 *    面向用户的租户通知请使用 database 通道。
 */
class TenantChannel
{
    /**
     * 给租户发送通知（暂时发送给租户下所有用户）
     *
     * @param  Tenant  $tenant  目标租户
     * @param  Notification  $notification  通知内容
     *
     * @throws InvalidArgumentException 通知不支持租户发送
     */
    public function send(Tenant $tenant, Notification $notification): void
    {
        if (method_exists($notification, 'toTenant')) {
            /** @var FilamentNotification $notify */
            $notify = $notification->toTenant($tenant);

            $notify->sendToDatabase($tenant->administrators);
        } else {
            throw new InvalidArgumentException('The notification is not supported.');
        }
    }
}
