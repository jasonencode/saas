<?php

namespace App\Notifications;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\BaseNotification;
use App\Contracts\Notification\DatabaseMessage;

class DemoNotification extends BaseNotification
{
    public static function getGroupTitle(): string
    {
        return '消息组标题';
    }

    public function via(Authenticatable $user): array
    {
        return ['database'];
    }

    public function toDatabase(Authenticatable $user): DatabaseMessage
    {
        return DatabaseMessage::make()
            ->title('Im title')
            ->body('【body】的通知');
    }
}
