<?php

namespace App\Notifications;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\BaseNotification;
use Filament\Notifications\Notification;

class DemoNotification extends BaseNotification
{
    public function via(Authenticatable $user): array
    {
        return ['database'];
    }

    public function databaseType(Authenticatable $user): string
    {
        return 'demo';
    }

    public function toDatabase(Authenticatable $user): array
    {
        return Notification::make()
            ->title('消息标题')
            ->body('【body】的通知')
            ->toDatabase()
            ->toArray();
    }
}
