<?php

namespace App\Channels;

use App\Models\Tenant;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;

class TenantChannel
{
    /**
     * 给租户发通知，这种情况，应该发给租户下的用户，具体角色下一步再说，先发所有人
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
