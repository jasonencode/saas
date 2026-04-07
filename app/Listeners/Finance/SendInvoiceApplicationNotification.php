<?php

namespace App\Listeners\Finance;

use App\Events\Finance\InvoiceApplicationSubmitted;
use App\Notifications\Finance\InvoiceApplicationSubmittedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendInvoiceApplicationNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(InvoiceApplicationSubmitted $event): void
    {
        $application = $event->application;
        $user = $application->user;

        // 发送通知给用户
        Notification::send($user, new InvoiceApplicationSubmittedNotification($application));

        // 发送通知给租户管理员（如果需要）
        // 这里可以根据实际需求获取租户管理员并发送通知
    }
}