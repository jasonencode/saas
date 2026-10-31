<?php

namespace App\Services\Mall\RefundActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\RefundStatus;
use App\Models\Mall\Order;
use App\Models\Mall\Refund;
use App\Services\System\EventLogService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * 修改退款金额（pending / waiting_return）
 *
 * 入口：RefundService::updateRefundAmount() / UpdateAmount::dispatch()。
 *
 * before：只有打款前的退款单可以改金额，金额不能为负
 * handle：加锁复核后写入商品金额与运费，并校验不超过订单剩余可退金额
 * log   ：写日志中心 refund.amount_updated（含改前/改后金额）
 * after ：空
 *
 * 已进入退款处理中或已完成的单据不允许修改，避免退款单金额与实际打款金额不一致。
 */
final class UpdateAmount extends AbstractOperation
{
    /**
     * 修改前的金额（handle 写入、log 读取）
     *
     * @var array{goods_amount: string, freight_amount: string, total: string}|null
     */
    private ?array $original = null;

    /**
     * @param  Refund  $refund  退款单
     * @param  Authenticatable  $operator  操作人（后台管理员）
     * @param  float  $goodsAmount  商品退款金额
     * @param  float  $freightAmount  退款运费
     */
    public function __construct(
        private readonly Refund $refund,
        private readonly Authenticatable $operator,
        private readonly float $goodsAmount,
        private readonly float $freightAmount = 0.0,
    ) {}

    /**
     * 前置：状态与金额合法性校验
     */
    public function before(): void
    {
        $this->assertUpdatable($this->refund);

        if ($this->goodsAmount < 0 || $this->freightAmount < 0) {
            throw new InvalidArgumentException('退款金额不能小于 0');
        }

        if (bccomp($this->total(), '0.01', 2) < 0) {
            throw new InvalidArgumentException('退款金额不能小于 0.01');
        }
    }

    /**
     * 金额落库
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            // 先锁订单再锁退款单，与退款申请保持一致，避免改金额与新建退款并发时超退
            Order::whereKey($this->refund->order_id)->lockForUpdate()->first();
            Refund::whereKey($this->refund->getKey())->lockForUpdate()->first();
            $this->refund->refresh();

            $this->assertUpdatable($this->refund);

            $this->original = [
                'goods_amount' => (string) $this->refund->goods_amount,
                'freight_amount' => (string) $this->refund->freight_amount,
                'total' => (string) $this->refund->total,
            ];

            $this->assertWithinOrder();

            $this->refund->update([
                'goods_amount' => number_format($this->goodsAmount, 2, '.', ''),
                'freight_amount' => number_format($this->freightAmount, 2, '.', ''),
                'total' => $this->total(),
            ]);

            return true;
        });
    }

    /**
     * 记录修改金额日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write('refund.amount_updated', '修改退款金额', $this->operator, $this->refund, null, [
            'goods_amount' => [
                'from' => $this->original['goods_amount'] ?? null,
                'to' => number_format($this->goodsAmount, 2, '.', ''),
            ],
            'freight_amount' => [
                'from' => $this->original['freight_amount'] ?? null,
                'to' => number_format($this->freightAmount, 2, '.', ''),
            ],
            'total' => [
                'from' => $this->original['total'] ?? null,
                'to' => $this->total(),
            ],
        ]);
    }

    /**
     * 状态校验（before 与锁内复核共用）
     */
    private function assertUpdatable(Refund $refund): void
    {
        if (!in_array($refund->status, [RefundStatus::Pending, RefundStatus::WaitingReturn], true)) {
            throw new RuntimeException('当前退款单状态不可修改金额');
        }
    }

    /**
     * 校验修改后的金额不超过订单剩余可退金额
     *
     * @throws InvalidArgumentException 超出实付金额或剩余可退运费
     */
    private function assertWithinOrder(): void
    {
        $order = Order::findOrFail($this->refund->order_id);

        $paid = number_format($order->getTotalAmount(), 2, '.', '');

        $otherTotal = $order->refunds()
            ->where('id', '!=', $this->refund->getKey())
            ->whereIn('status', RefundStatus::effectiveCases())
            ->sum('total');

        if (bccomp(bcadd(number_format((float) $otherTotal, 2, '.', ''), $this->total(), 2), $paid, 2) === 1) {
            throw new InvalidArgumentException('退款总额超出订单实付金额');
        }

        $otherFreight = $order->refunds()
            ->where('id', '!=', $this->refund->getKey())
            ->whereIn('status', RefundStatus::effectiveCases())
            ->sum('freight_amount');

        $freight = number_format($this->freightAmount, 2, '.', '');
        $orderFreight = number_format((float) $order->freight, 2, '.', '');

        if (bccomp(bcadd(number_format((float) $otherFreight, 2, '.', ''), $freight, 2), $orderFreight, 2) === 1) {
            throw new InvalidArgumentException('退款运费超出订单实付运费');
        }
    }

    /**
     * 修改后的退款总额
     */
    private function total(): string
    {
        return bcadd(
            number_format($this->goodsAmount, 2, '.', ''),
            number_format($this->freightAmount, 2, '.', ''),
            2,
        );
    }
}
