<?php

namespace App\Listeners\Mall;

use App\Events\Mall\RefundFailed;
use App\Notifications\Mall\RefundFailedUserNotification;
use Throwable;

/**
 * 退款失败通知用户
 */
class RefundFailedListener
{
    public function handle(RefundFailed $event): void
    {
        try {
            $event->refund->user->notify(new RefundFailedUserNotification($event->refund));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
