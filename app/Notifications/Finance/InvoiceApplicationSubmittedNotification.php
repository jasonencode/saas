<?php

namespace App\Notifications\Finance;

use App\Models\InvoiceApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceApplicationSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public InvoiceApplication $application)
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('发票申请已提交')
            ->greeting('您好！')
            ->line('您的发票申请已成功提交，我们将尽快处理。')
            ->line('申请金额：¥' . $this->application->amount)
            ->line('申请原因：' . $this->application->reason)
            ->action('查看申请详情', url('/user/invoices/applications/' . $this->application->id))
            ->line('感谢您的使用！');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invoice_application_id' => $this->application->id,
            'amount' => $this->application->amount,
            'reason' => $this->application->reason,
            'status' => $this->application->status->value,
            'message' => '您的发票申请已成功提交，我们将尽快处理。',
        ];
    }
}