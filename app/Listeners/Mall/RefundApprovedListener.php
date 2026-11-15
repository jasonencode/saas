<?php

namespace App\Listeners\Mall;

use App\Events\Mall\RefundApproved;
use App\Notifications\Mall\RefundApprovedNotification;
use Throwable;

/**
 * 退款审核通过通知用户
 */
class RefundApprovedListener
{
    public function handle(RefundApproved $event): void
    {
        try {
            $event->refund->user->notify(new RefundApprovedNotification($event->refund));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
