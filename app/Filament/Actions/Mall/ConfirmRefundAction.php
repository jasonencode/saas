<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Finance\PaymentRefundStatus;
use App\Enums\Mall\RefundStatus;
use App\Models\Mall\Refund;
use App\Services\Mall\RefundService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use RuntimeException;
use Throwable;

/**
 * 退款处理动作
 *
 * 未创建支付退款单时发起退款；支付退款已成功（异步回调未触发/人工补单）时确认完成。
 * 支付退款处理中则提示等待财务处理，避免重复创建退款单。
 */
class ConfirmRefundAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'confirmRefund';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(fn (Refund $refund): string => self::paymentRefundCompleted($refund) ? '确认退款' : '发起退款');
        $this->icon(Heroicon::OutlinedBanknotes);
        $this->color('success');

        $this->visible(fn (Refund $refund): bool => userCan(self::getDefaultName(), $refund) && $refund->status === RefundStatus::Processing);

        $this->requiresConfirmation();
        $this->modalHeading(fn (Refund $refund): string => self::paymentRefundCompleted($refund) ? '确认退款' : '发起退款');
        $this->modalDescription(fn (Refund $refund): string => self::paymentRefundCompleted($refund)
            ? '支付退款已成功，确认完成售后单？'
            : '确定要发起退款吗？将创建支付退款单，财务审核后按原支付通道退回给用户。');

        $this->action(function (Refund $refund): void {
            try {
                $paymentRefund = $refund->paymentRefunds()->latest('id')->first();

                if ($paymentRefund && $paymentRefund->status !== PaymentRefundStatus::Completed) {
                    throw new RuntimeException('支付退款单处理中，请等待财务处理');
                }

                if ($paymentRefund) {
                    service(RefundService::class)
                        ->confirmRefund($refund, Filament::auth()->user(), '人工确认退款');

                    $this->successNotificationTitle('退款完成');
                } else {
                    service(RefundService::class)
                        ->processRefund($refund, Filament::auth()->user());

                    $this->successNotificationTitle('退款已发起');
                }

                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('退款处理失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }

    /**
     * 支付退款是否已成功
     */
    private static function paymentRefundCompleted(Refund $refund): bool
    {
        if ($refund->relationLoaded('paymentRefunds')) {
            return $refund->paymentRefunds
                ->contains(fn ($paymentRefund): bool => $paymentRefund->status === PaymentRefundStatus::Completed);
        }

        return $refund->paymentRefunds()
            ->where('status', PaymentRefundStatus::Completed)
            ->exists();
    }
}
