<?php

namespace App\Filament\Actions\Finance;

use App\Enums\Finance\PaymentRefundStatus;
use App\Models\Finance\PaymentRefund;
use App\Services\Finance\PaymentRefundService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Throwable;

class RetryPaymentRefundAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'retryRefund';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('重试退款');
        $this->icon(Heroicon::OutlinedArrowPath);
        $this->color('warning');

        $this->visible(fn (PaymentRefund $record): bool => userCan(self::getDefaultName(), $record) && $record->status === PaymentRefundStatus::Failed);

        $this->requiresConfirmation();
        $this->modalHeading('重试退款');
        $this->modalDescription('将重新发起原路退回，确认操作？');

        $this->action(function (PaymentRefund $record): void {
            try {
                service(PaymentRefundService::class)->retry($record);

                $this->successNotificationTitle('退款已重新提交');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('重试失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }
}
