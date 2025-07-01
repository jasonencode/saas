<?php

namespace App\Notifications;

use App\Contracts\Authenticatable;
use App\Contracts\DatabaseMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

// use Illuminate\Contracts\Queue\ShouldQueue; implements ShouldQueue

abstract class BaseNotification extends Notification
{
    use Queueable;

//    public string $queue = 'notification';
//    public int $delay = 5;
//    public string $connection = '';

    abstract public static function getTitle(): string;

    abstract public function via(Authenticatable $notifiable): array;

    abstract public function toDatabase(Authenticatable $notifiable): DatabaseMessage;
}
