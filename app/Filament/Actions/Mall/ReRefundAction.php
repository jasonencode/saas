<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Finance\PaymentRefundStatus;
use App\Enums\Mall\RefundStatus;
use App\Models\Finance\PaymentRefund;
use App\Models\Mall\Refund;
use App\Services\Mall\RefundService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Throwable;

/**
 * 重新发起退款
 *
 * 仅当支付退款单已成功或已失败时可操作：已成功补完成售后单，已失败重试原支付通道。
 */
class ReRefundAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'reRefund';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(fn (Refund $refund): string => self::latestPaymentRefundCompleted($refund) ? '确认退款' : '重新发起退款');
        $this->icon(Heroicon::OutlinedArrowPathRoundedSquare);
        $this->color('warning');

        $this->visible(fn (Refund $refund): bool => userCan(self::getDefaultName(), $refund)
            && in_array($refund->status, [RefundStatus::Processing, RefundStatus::Failed], true)
            && self::hasRetryablePaymentRefund($refund));

        $this->requiresConfirmation();
        $this->modalHeading(fn (Refund $refund): string => self::latestPaymentRefundCompleted($refund) ? '确认退款' : '重新发起退款');
        $this->modalDescription(fn (Refund $refund): string => self::latestPaymentRefundCompleted($refund)
            ? '支付退款已成功，确认补完成售后单？'
            : '将重新调用原支付通道退回款项，确定继续吗？');

        $this->action(function (Refund $refund): void {
            try {
                service(RefundService::class)
                    ->reRefund($refund, Filament::auth()->user());

                $this->successNotificationTitle('退款重试成功');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('重新发起退款失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }

    /**
     * 最新的支付退款单
     */
    private static function latestPaymentRefund(Refund $refund): ?PaymentRefund
    {
        if ($refund->relationLoaded('paymentRefunds')) {
            return $refund->paymentRefunds->sortByDesc('id')->first();
        }

        return $refund->paymentRefunds()->latest('id')->first();
    }

    /**
     * 支付退款单是否已成功
     */
    private static function latestPaymentRefundCompleted(Refund $refund): bool
    {
        return self::latestPaymentRefund($refund)?->status === PaymentRefundStatus::Completed;
    }

    /**
     * 是否存在可重试的支付退款单（已成功或已失败）
     */
    private static function hasRetryablePaymentRefund(Refund $refund): bool
    {
        return in_array(self::latestPaymentRefund($refund)?->status, [
            PaymentRefundStatus::Completed,
            PaymentRefundStatus::Failed,
        ], true);
    }
}
