<?php

namespace App\Console\Commands\Finance;

use App\Console\Commands\BaseCommand;
use App\Contracts\Attributes\CommandLabel;
use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\RechargeOrderStatus;
use App\Models\Finance\PaymentOrder;
use App\Models\Finance\RechargeOrder;
use App\Services\Finance\WechatPaymentResolver;
use App\Services\Foundation\WechatPaymentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

/**
 * 关闭超时未支付单
 *
 * 支付单默认 30 分钟过期；过期后本地置为已取消并尽力关闭微信订单，
 * 避免列表长期堆积待支付单、旧 prepay 继续被支付。
 */
#[Signature('app:finance:expire-payment-orders')]
#[Description('关闭超时未支付的支付单与充值单')]
#[CommandLabel('关闭超时支付单')]
class ExpirePaymentOrdersCommand extends BaseCommand
{
    /**
     * 本次关闭的支付单数量
     */
    protected int $closed = 0;

    /**
     * 本次关闭的充值单数量
     */
    protected int $expiredRecharges = 0;

    /**
     * 本次失败数量
     */
    protected int $failed = 0;

    public function handle(): int
    {
        PaymentOrder::query()
            ->where('status', PaymentStatus::Pending)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->chunkById(100, function (Collection $payments): void {
                foreach ($payments as $payment) {
                    try {
                        $affected = PaymentOrder::whereKey($payment->getKey())
                            ->where('status', PaymentStatus::Pending)
                            ->update(['status' => PaymentStatus::Canceled]);

                        if ($affected === 0) {
                            continue;
                        }

                        $this->closed++;

                        // 尽力关闭微信订单；失败只上报，不影响本地取消状态
                        if ($payment->gateway === PaymentGateway::Wechat) {
                            $this->closeWechatOrder($payment);
                        }
                    } catch (Throwable $e) {
                        $this->failed++;
                        $this->error("支付单 [$payment->no] 关闭失败: ".$e->getMessage());
                    }
                }
            });

        RechargeOrder::query()
            ->where('status', RechargeOrderStatus::Pending)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->chunkById(100, function (Collection $orders): void {
                foreach ($orders as $order) {
                    try {
                        $affected = RechargeOrder::whereKey($order->getKey())
                            ->where('status', RechargeOrderStatus::Pending)
                            ->update(['status' => RechargeOrderStatus::Canceled]);

                        if ($affected > 0) {
                            $this->expiredRecharges++;
                        }
                    } catch (Throwable $e) {
                        $this->failed++;
                        $this->error("充值单 [$order->no] 关闭失败: ".$e->getMessage());
                    }
                }
            });

        $this->info(sprintf(
            '过期处理完成：支付单 %d 笔，充值单 %d 笔，失败 %d 笔。',
            $this->closed,
            $this->expiredRecharges,
            $this->failed,
        ));

        $this->logContext([
            'closed' => $this->closed,
            'expired_recharges' => $this->expiredRecharges,
            'failed' => $this->failed,
        ]);

        return $this->failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * 关闭微信支付订单
     *
     * @param  PaymentOrder  $payment  支付单
     */
    private function closeWechatOrder(PaymentOrder $payment): void
    {
        try {
            $config = WechatPaymentResolver::forPayment($payment, requireEnabled: false);

            service(WechatPaymentService::class)->close($config, $payment->no, requireEnabled: false);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
