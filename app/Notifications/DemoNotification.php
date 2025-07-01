<?php

namespace App\Notifications;

use App\Contracts\Authenticatable;
use App\Contracts\DatabaseMessage;

class DemoNotification extends BaseNotification
{
    public static function getTitle(): string
    {
        return '新订单';
    }

    public function via(Authenticatable $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(Authenticatable $notifiable): DatabaseMessage
    {
        return DatabaseMessage::make()
            ->title(self::getTitle())
            ->template('【{name}】的通知')
            ->args(['name' => '张三']);
    }
}
