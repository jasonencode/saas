<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\RefundStatus;
use App\Models\Mall\Refund;
use App\Services\Finance\PaymentRefundService;
use App\Services\Mall\RefundService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ConfirmRefundAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'confirmRefund';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('提交退款');
        $this->icon(Heroicon::OutlinedBanknotes);
        $this->color('success');

        $this->visible(fn (Refund $refund): bool => userCan(self::getDefaultName(), $refund)
            && $refund->status === RefundStatus::Processing
            && $refund->paymentRefunds()->whereIn('status', PaymentRefundService::OCCUPIED_STATUSES)->doesntExist());

        $this->requiresConfirmation();
        $this->modalHeading('提交退款');
        $this->modalDescription('提交后将生成支付退款单，财务确认后原路退回；资金到账前售后单保持处理中。');

        $this->schema([
            Forms\Components\Textarea::make('remark')
                ->label('退款备注')
                ->rows(3)
                ->maxLength(500),
        ]);
        $this->action(function (Refund $refund, array $data): void {
            try {
                service(RefundService::class)
                    ->confirmRefund($refund, Filament::auth()->user(), $data['remark'] ?? null);

                $this->successNotificationTitle('退款已提交，等待财务打款');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('退款提交失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }
}
