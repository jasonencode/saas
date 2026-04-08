<?php

namespace App\Notifications\Finance;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\BaseNotification;
use App\Models\InvoiceApplication;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * 发票申请提交通知
 */
class InvoiceApplicationSubmittedNotification extends BaseNotification
{
    public function __construct(public InvoiceApplication $application)
    {
        //
    }

    /**
     * 获取通知分组标题
     */
    public static function getGroupTitle(): string
    {
        return '发票通知';
    }

    /**
     * 获取通知类型
     */
    public static function getType(): string
    {
        return 'invoice_application_submitted';
    }

    /**
     * 获取通知图标
     */
    public function getIcon(): string
    {
        return 'receipt';
    }

    /**
     * 获取通知颜色
     */
    public function getColor(): string
    {
        return 'success';
    }

    /**
     * 发送通道
     */
    public function via(Authenticatable $user): array
    {
        return ['mail', 'database'];
    }

    /**
     * 邮件通知
     */
    public function toMail(Authenticatable $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('发票申请已提交')
            ->greeting('您好！')
            ->line('您的发票申请已成功提交，我们将尽快处理。')
            ->line('申请金额：¥'.$this->application->amount)
            ->line('申请原因：'.$this->application->reason)
            ->action('查看申请详情', $this->getUrl($notifiable))
            ->line('感谢您的使用！');
    }

    /**
     * 获取通知消息
     */
    public function getMessage(): string
    {
        return '您的发票申请已成功提交，我们将尽快处理。';
    }

    /**
     * 获取通知数据
     */
    protected function getData(): array
    {
        return [
            'invoice_application_id' => $this->application->id,
            'amount' => $this->application->amount,
            'reason' => $this->application->reason,
            'status' => $this->application->status->value,
        ];
    }

    /**
     * 获取通知链接
     */
    public function getUrl(Authenticatable $notifiable): string
    {
        return url('/user/invoices/applications/'.$this->application->id);
    }
}
