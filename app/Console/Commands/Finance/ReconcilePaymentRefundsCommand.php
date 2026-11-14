<?php

namespace App\Console\Commands\Finance;

use App\Console\Commands\BaseCommand;
use App\Contracts\Attributes\CommandLabel;
use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\PaymentRefundStatus;
use App\Models\Finance\PaymentRefund;
use App\Models\Foundation\WechatPayment;
use App\Services\Finance\PaymentRefundService;
use App\Services\Foundation\WechatPaymentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Throwable;

#[Signature('app:finance:reconcile-payment-refunds {--minutes=5 : 只处理创建超过 N 分钟的退款单}')]
#[Description('微信退款对账：查询处理中的退款单并回写最终状态')]
#[CommandLabel('微信退款对账')]
class ReconcilePaymentRefundsCommand extends BaseCommand
{
    /**
     * 本次执行的回写成功数量
     */
    protected int $completed = 0;

    /**
     * 本次执行仍处理中的数量
     */
    protected int $pending = 0;

    /**
     * 本次执行失败数量
     */
    protected int $failed = 0;

    public function handle(PaymentRefundService $service): int
    {
        $minutes = max(1, (int) $this->option('minutes'));

        PaymentRefund::query()
            ->where('status', PaymentRefundStatus::Processing)
            ->where('created_at', '<=', now()->subMinutes($minutes))
            ->whereHas('paymentOrder', fn ($query) => $query->where('gateway', PaymentGateway::Wechat))
            ->chunkById(100, function ($refunds) use ($service): void {
                foreach ($refunds as $refund) {
                    $this->reconcile($service, $refund);
                }
            });

        $this->info(sprintf(
            '对账完成：已到账 %d 笔，仍处理中 %d 笔，失败 %d 笔。',
            $this->completed,
            $this->pending,
            $this->failed,
        ));

        $this->logContext([
            'completed' => $this->completed,
            'pending' => $this->pending,
            'failed' => $this->failed,
        ]);

        return self::SUCCESS;
    }

    /**
     * 查询并回写单笔退款状态
     *
     * @param  PaymentRefundService  $service  支付退款服务
     * @param  PaymentRefund  $refund  退款单
     */
    private function reconcile(PaymentRefundService $service, PaymentRefund $refund): void
    {
        try {
            $config = WechatPayment::ofTenant($refund->tenant_id)
                ->orderByDesc('status')
                ->first();

            if (!$config) {
                $this->failed++;
                $this->error("退款单 [$refund->no] 未配置微信支付，跳过");

                return;
            }

            $result = service(WechatPaymentService::class)->queryRefund($config, $refund->no);
            $status = $result['status'] ?? null;

            if ($status === 'SUCCESS') {
                if ($refund->status === PaymentRefundStatus::Processing) {
                    $service->complete($refund);
                }

                $this->completed++;
                $this->line("退款单 [$refund->no] 已确认到账");

                return;
            }

            if (in_array($status, ['CLOSED', 'ABNORMAL'], true)) {
                $service->failFromGateway($refund, '微信退款关闭或异常：'.$status);
                $this->failed++;
                $this->error("退款单 [$refund->no] 状态异常：$status");

                return;
            }

            $this->pending++;
        } catch (Throwable $e) {
            $this->failed++;
            report($e);
            $this->error("退款单 [$refund->no] 对账失败：".$e->getMessage());
        }
    }
}
