<?php

namespace App\Filament\Actions\Finance;

use App\Enums\Finance\PaymentRefundStatus;
use App\Models\Finance\PaymentRefund;
use App\Services\Finance\PaymentRefundService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ExecutePaymentRefundAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'executeRefund';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('执行退款');
        $this->icon(Heroicon::OutlinedBanknotes);
        $this->color('success');

        $this->visible(fn (PaymentRefund $record): bool => userCan(self::getDefaultName(), $record) && $record->status === PaymentRefundStatus::Approved);

        $this->requiresConfirmation();
        $this->modalHeading('执行退款');
        $this->modalDescription('将按原支付通道退回款项（微信原路退回 / 余额退回账户），确认执行？');

        $this->action(function (PaymentRefund $record): void {
            try {
                service(PaymentRefundService::class)->execute($record);

                $this->successNotificationTitle('退款已提交');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('退款失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }
}
