<?php

namespace App\Channels;

use App\Models\System\Tenant;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;

/**
 * 租户通知通道
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
