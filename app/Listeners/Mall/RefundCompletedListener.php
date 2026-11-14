<?php

namespace App\Listeners\Mall;

use App\Events\Mall\RefundCompleted;
use App\Notifications\Mall\RefundCompletedUserNotification;
use Throwable;

/**
 * 退款完成通知用户
 */
class RefundCompletedListener
{
    public function handle(RefundCompleted $event): void
    {
        try {
            $event->refund->user->notify(new RefundCompletedUserNotification($event->refund));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
