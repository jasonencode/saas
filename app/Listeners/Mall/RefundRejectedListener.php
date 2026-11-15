<?php

namespace App\Listeners\Mall;

use App\Events\Mall\RefundRejected;
use App\Notifications\Mall\RefundRejectedNotification;
use Throwable;

/**
 * 退款审核驳回通知用户
 */
class RefundRejectedListener
{
    public function handle(RefundRejected $event): void
    {
        try {
            $event->refund->user->notify(new RefundRejectedNotification(
                $event->refund,
                (string) $event->refund->approval_remark,
            ));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
