<?php

namespace App\Listeners\Mall;

use App\Events\Mall\RefundInitialized;
use App\Jobs\Mall\ProcessExpressRefundJob;

/**
 * 极速退款：退款单落库后异步执行自动退款
 */
class ExpressRefundOnInitialized
{
    public function handle(RefundInitialized $event): void
    {
        if (!$event->refund->is_express) {
            return;
        }

        ProcessExpressRefundJob::dispatch($event->refund);
    }
}
