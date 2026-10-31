<?php

namespace App\Services\Mall\RefundActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Enums\Mall\RefundStatus;
use App\Enums\Mall\RefundType;
use App\Events\Mall\RefundInitialized;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use App\Models\Mall\Refund;
use App\Models\Mall\RefundItem;
use App\Services\Mall\DTOs\RefundData;
use App\Services\System\EventLogService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * 申请退款（创建退款单）
 *
 * 入口：RefundService::createRefund() / Apply::dispatch()。
 *
 * before：订单可退、退款类型与订单状态匹配、退款商品归属与可退数量校验
 * handle：按订单加锁后复核并建单落库（算金额与运费、写明细），仅退款自动审核通过
 * log   ：写日志中心 refund.applied（自动审核时补 refund.approved / refund.processing）
 * after ：空
 */
final class Apply extends AbstractOperation
{
    /**
     * 是否已自动审核通过（handle 写入、log 读取）
     */
    private bool $autoApproved = false;

    /**
     * 本次申请涉及的订单项（锁内校验后复用，保证金额与数量同源）
     *
     * @var Collection<int, OrderItem>|null
     */
    private ?Collection $validatedOrderItems = null;

    /**
     * @param  Order  $order  订单
     * @param  Authenticatable  $user  申请人（用户或代用户退款的操作人）
     * @param  RefundData  $data  退款数据（已校验）
     * @param  bool  $operatorInitiated  是否为运营/租户代用户发起
     */
    public function __construct(
        private readonly Order $order,
        private readonly Authenticatable $user,
        private readonly RefundData $data,
        private readonly bool $operatorInitiated = false,
    ) {}

    /**
     * 前置：订单、退款类型与退款商品校验
     */
    public function before(): void
    {
        if ($this->operatorInitiated) {
            self::assertOperatorOrderRefundable($this->order);
        } else {
            self::assertOrderRefundable($this->order);
        }

        $this->assertRefundType();
        $this->assertRefundItems();
    }

    /**
     * 按订单加锁后创建退款单
     *
     * @throws Throwable
     */
    public function handle(): Refund
    {
        return DB::transaction(fn (): Refund => $this->createRefund());
    }

    /**
     * 记录申请退款日志
     *
     * @param  mixed  $result  handle() 返回的退款单
     */
    public function log(mixed $result): void
    {
        if (!$result instanceof Refund) {
            return;
        }

        $eventLog = service(EventLogService::class);

        $eventLog->write('refund.applied', '申请退款', $this->user, $result, null, [
            'type' => $this->data->type->value,
            'reason' => $this->data->reason->value,
            'reason_detail' => $this->data->reasonDetail,
            'items_count' => count($this->data->items),
            'goods_amount' => $result->goods_amount,
            'freight_amount' => $result->freight_amount,
            'total' => $result->total,
        ]);

        // 订单时间线保留退款申请记录
        $eventLog->write('order.refund_applied', '提交退款申请', $this->user, $this->order, null, [
            'refund_no' => $result->no,
            'refund_amount' => $result->total,
        ]);

        if (!$this->autoApproved) {
            return;
        }

        $eventLog->write('refund.approved', '系统自动审核通过', $this->user, $result, null, [
            'previous_status' => RefundStatus::Pending->value,
            'next_status' => RefundStatus::Processing->value,
            'auto_approved' => true,
        ]);

        $eventLog->write('refund.processing', '自动进入退款处理', $this->user, $result, null, [
            'status' => RefundStatus::Processing->value,
        ]);
    }

    /**
     * 落库：退款单与明细；仅退款自动审核通过
     */
    private function createRefund(): Refund
    {
        // 行锁串行化校验与扣减，防并发重复申请导致超退
        Order::whereKey($this->order->getKey())->lockForUpdate()->first();
        $this->order->refresh();

        if ($this->operatorInitiated) {
            self::assertOperatorOrderRefundable($this->order);
        } else {
            self::assertOrderRefundable($this->order);
        }

        $this->assertRefundType();
        $this->assertRefundItems();

        $amounts = $this->calculateRefundAmount();

        $refund = Refund::create([
            'tenant_id' => $this->order->tenant_id,
            'user_id' => $this->order->user_id,
            'order_id' => $this->order->id,
            'total' => $amounts['total'],
            'goods_amount' => $amounts['goods_amount'],
            'freight_amount' => $amounts['freight_amount'],
            'status' => RefundStatus::Pending,
            'type' => $this->data->type,
            'reason' => $this->data->reason,
            'reason_detail' => $this->data->reasonDetail,
        ]);

        foreach ($this->data->items as $item) {
            $orderItem = $this->validatedOrderItems?->get((int) $item->orderItemId);

            if (!$orderItem) {
                throw new InvalidArgumentException('退款商品不属于当前订单');
            }

            $refund->items()->create([
                'order_item_id' => $orderItem->getKey(),
                'qty' => $item->qty,
                'price' => $orderItem->price,
                'remark' => $item->remark,
            ]);
        }

        // 仅退款无需退货，自动审核通过直接进入退款处理；退货退款等待商家审核
        $this->autoApproved = $this->data->type === RefundType::OnlyRefund;

        if ($this->autoApproved) {
            $refund->update([
                'status' => RefundStatus::Processing,
                'approved_at' => now(),
            ]);
        }

        RefundInitialized::dispatch($refund, $this->user);

        return $refund;
    }

    /**
     * 验证订单是否可退款
     *
     * @param  Order  $order  订单
     *
     * @throws InvalidArgumentException 订单状态不支持退款
     * @throws RuntimeException 已有退款申请正在处理中
     */
    public static function assertOrderRefundable(Order $order): void
    {
        // 允许的退款类型由履约方式 + 订单状态共同决定，空数组即不可退款
        if (self::allowedTypes($order) === []) {
            throw new InvalidArgumentException('当前订单状态不支持退款');
        }

        $hasPendingRefund = $order->refunds()
            ->whereIn('status', RefundStatus::activeCases())
            ->exists();

        if ($hasPendingRefund) {
            throw new RuntimeException('已有退款申请正在处理中');
        }
    }

    /**
     * 运营 / 租户代用户退款：校验订单状态与活跃退款
     *
     * 与用户自助申请的区别：允许已完成等售后状态由商家代发起，
     * 但订单必须已付款、无处理中退款，商品数量与金额仍按同一套规则严格校验。
     *
     * @param  Order  $order  订单
     *
     * @throws InvalidArgumentException 订单状态不支持退款
     * @throws RuntimeException 已有退款申请正在处理中
     */
    public static function assertOperatorOrderRefundable(Order $order): void
    {
        if (self::operatorAllowedTypes($order) === []) {
            throw new InvalidArgumentException('当前订单状态不支持退款');
        }

        $hasPendingRefund = $order->refunds()
            ->whereIn('status', RefundStatus::activeCases())
            ->exists();

        if ($hasPendingRefund) {
            throw new RuntimeException('已有退款申请正在处理中');
        }
    }

    /**
     * 运营 / 租户代用户退款允许的类型
     *
     * 代用户退款可由商家灵活选择仅退款或退货退款，但仍受履约方式约束：
     * 虚拟商品仅支持仅退款；待自提商品尚未交付，仅支持仅退款。
     *
     * @param  Order  $order  订单
     *
     * @return RefundType[]
     */
    public static function operatorAllowedTypes(Order $order): array
    {
        if ($order->fulfillment_type === FulfillmentType::Virtual) {
            return $order->status === OrderStatus::Completed ? [RefundType::OnlyRefund] : [];
        }

        if ($order->fulfillment_type === FulfillmentType::Pickup) {
            return match ($order->status) {
                OrderStatus::PickupPending => [RefundType::OnlyRefund],
                OrderStatus::Verified, OrderStatus::Completed => RefundType::cases(),
                default => [],
            };
        }

        return match ($order->status) {
            OrderStatus::Paid,
            OrderStatus::Preparing,
            OrderStatus::PartiallyShipped,
            OrderStatus::Delivered,
            OrderStatus::Signed,
            OrderStatus::Completed => RefundType::cases(),
            default => [],
        };
    }

    /**
     * 获取订单允许的退款类型
     *
     * 综合订单状态与履约方式判断，用于 API 响应与校验：
     * - 虚拟商品：仅 Completed 支持仅退款（无实物可退）
     * - 门店自提：PickupPending 仅退款；Verified 退货退款
     * - 快递邮寄：未发货支持两种；已发货仅退货退款；Completed 不再受理售后
     *
     * @param  Order  $order  订单
     *
     * @return RefundType[]
     */
    public static function allowedTypes(Order $order): array
    {
        return match ($order->fulfillment_type) {
            // 虚拟商品支付即完成，只能仅退款
            FulfillmentType::Virtual => $order->status === OrderStatus::Completed
                ? [RefundType::OnlyRefund]
                : [],
            // 自提：待核销时商品仍在商家处，仅退款；已核销后按退货退款处理
            FulfillmentType::Pickup => match ($order->status) {
                OrderStatus::PickupPending => [RefundType::OnlyRefund],
                OrderStatus::Verified => [RefundType::ReturnRefund],
                default => [],
            },
            // 快递：未发货可仅退款/退货退款，已发货只能退货退款；已完成订单不再受理售后
            default => match ($order->status) {
                OrderStatus::Paid, OrderStatus::Preparing => RefundType::cases(),
                OrderStatus::PartiallyShipped, OrderStatus::Delivered, OrderStatus::Signed => [RefundType::ReturnRefund],
                default => [],
            },
        };
    }

    /**
     * 验证退款类型与订单状态的匹配性
     *
     * @throws InvalidArgumentException
     */
    private function assertRefundType(): void
    {
        $allowedTypes = $this->operatorInitiated
            ? self::operatorAllowedTypes($this->order)
            : self::allowedTypes($this->order);

        if (in_array($this->data->type, $allowedTypes, true)) {
            return;
        }

        if ($this->operatorInitiated) {
            throw new InvalidArgumentException('当前订单状态不支持该退款类型');
        }

        if ($this->order->fulfillment_type === FulfillmentType::Virtual) {
            throw new InvalidArgumentException(
                $this->data->type === RefundType::ReturnRefund
                    ? '虚拟商品不支持退货退款'
                    : '当前订单状态不支持退款'
            );
        }

        if ($this->order->fulfillment_type === FulfillmentType::Pickup) {
            if ($this->order->status === OrderStatus::PickupPending && $this->data->type === RefundType::ReturnRefund) {
                throw new InvalidArgumentException('待自提订单尚未交付，请选择仅退款');
            }

            if ($this->order->status === OrderStatus::Verified && $this->data->type === RefundType::OnlyRefund) {
                throw new InvalidArgumentException('已核销的自提订单请选择退货退款');
            }
        }

        if (
            in_array($this->order->status, [OrderStatus::PartiallyShipped, OrderStatus::Delivered, OrderStatus::Signed], true)
            && $this->data->type === RefundType::OnlyRefund
        ) {
            throw new InvalidArgumentException('已发货的订单不支持仅退款，请选择退货退款');
        }

        throw new InvalidArgumentException('当前订单状态不支持该退款类型');
    }

    /**
     * 验证退款商品
     *
     * 严格校验：至少一件、同一商品不可重复提交、必须属于当前订单、数量必须为正且不超过剩余可退数量。
     *
     * @throws InvalidArgumentException 商品为空、重复、不属于当前订单或可退数量不足
     */
    private function assertRefundItems(): void
    {
        if ($this->data->items === []) {
            throw new InvalidArgumentException('请至少选择一个退款商品');
        }

        $orderItems = $this->order->items()
            ->with('refundItems.refund')
            ->get()
            ->keyBy(fn (OrderItem $item): int => (int) $item->getKey());

        $this->validatedOrderItems = $orderItems;

        $seen = [];

        foreach ($this->data->items as $item) {
            $orderItemId = (int) $item->orderItemId;

            if (isset($seen[$orderItemId])) {
                throw new InvalidArgumentException('退款商品重复，请合并相同商品后再提交');
            }

            $seen[$orderItemId] = true;

            if ($item->qty < 1) {
                throw new InvalidArgumentException('退款数量必须大于 0');
            }

            $orderItem = $orderItems->get($orderItemId);

            if (!$orderItem) {
                throw new InvalidArgumentException('退款商品不属于当前订单');
            }

            $refundableQty = $orderItem->getMaxRefundCounts();

            if ($item->qty > $refundableQty) {
                throw new InvalidArgumentException(sprintf(
                    '商品「%s」可退数量为 %d，退款数量不能超过可退数量',
                    $orderItem->orderable?->getOrderableName() ?? $orderItem->orderable_name,
                    $refundableQty,
                ));
            }
        }
    }

    /**
     * 计算退款金额
     *
     * 商品金额以订单项真实单价 × 退款数量计算（不信任客户端传入的价格），
     * 并按订单券抵扣比例分摊到订单项，保证任何退款组合总额不超过实付金额；
     * 运费按退款类型处理，上限为「订单运费 − Σ已退运费」：
     * - 仅退款（未发货订单）：退还剩余可退运费
     * - 退货退款（已发货订单）：按申请退运费退还
     *
     * @throws InvalidArgumentException 退款总额超出订单实付金额
     *
     * @return array{goods_amount: string, freight_amount: string, total: string} 退款金额明细
     */
    private function calculateRefundAmount(): array
    {
        $orderItems = $this->validatedOrderItems ?? $this->order->items()
            ->with('refundItems.refund')
            ->get()
            ->keyBy(fn (OrderItem $item): int => (int) $item->getKey());

        $netAmounts = $this->netItemAmounts($orderItems);

        $goodsAmount = '0.00';

        foreach ($this->data->items as $item) {
            $orderItemId = (int) $item->orderItemId;
            $orderItem = $orderItems->get($orderItemId);

            if (!$orderItem) {
                throw new InvalidArgumentException('退款商品不属于当前订单');
            }

            $netAmount = $netAmounts[$orderItemId] ?? '0.00';
            $unitPrice = $this->netUnitPrice($netAmount, $orderItem->qty);

            // 剩余可退 = 分摊后可退金额 − 已退金额（按同口径累计），兜住单价尾差
            $refundedQty = $this->refundedQtyOf($orderItemId);
            $remainingAmount = bcsub($netAmount, bcmul($unitPrice, (string) $refundedQty, 2), 2);

            $itemAmount = bcmul($unitPrice, (string) $item->qty, 2);
            if (bccomp($itemAmount, $remainingAmount, 2) === 1) {
                $itemAmount = $remainingAmount;
            }

            $goodsAmount = bcadd($goodsAmount, $itemAmount, 2);
        }

        $freightAmount = $this->calculateRefundFreight();
        $total = bcadd($goodsAmount, $freightAmount, 2);

        $this->assertRefundWithinPaid($total);

        return [
            'goods_amount' => $goodsAmount,
            'freight_amount' => $freightAmount,
            'total' => $total,
        ];
    }

    /**
     * 计算各订单项分摊抵扣后的可退金额
     *
     * 读取下单时落库的分摊快照（order_items.coupon_discount），不重新计算，
     * 保证与展示口径一致。
     *
     * @param  Collection<int, OrderItem>  $orderItems  订单项
     *
     * @return array<int, string> 订单项 ID => 分摊后可退金额
     */
    private function netItemAmounts(Collection $orderItems): array
    {
        $amounts = [];

        foreach ($orderItems as $orderItem) {
            $subtotal = bcmul((string) $orderItem->price, (string) $orderItem->qty, 2);
            $share = number_format((float) $orderItem->coupon_discount, 2, '.', '');

            $amounts[(int) $orderItem->getKey()] = bcsub($subtotal, $share, 2);
        }

        return $amounts;
    }

    /**
     * 计算分摊后的可退单价（保留 2 位）
     *
     * @param  string  $netAmount  订单项分摊后可退金额
     * @param  int  $qty  订单项数量
     */
    private function netUnitPrice(string $netAmount, int $qty): string
    {
        if ($qty < 1) {
            return '0.00';
        }

        return number_format((float) bcdiv($netAmount, (string) $qty, 4), 2, '.', '');
    }

    /**
     * 订单项已占用（进行中/已完成）的退款数量
     */
    private function refundedQtyOf(int $orderItemId): int
    {
        $orderItem = $this->validatedOrderItems?->get($orderItemId);

        if ($orderItem) {
            return max(0, (int) $orderItem->qty - $orderItem->getMaxRefundCounts());
        }

        return (int) RefundItem::where('order_item_id', $orderItemId)
            ->whereHas('refund', fn ($query) => $query->whereIn('status', RefundStatus::effectiveCases()))
            ->sum('qty');
    }

    /**
     * 计算本笔退款运费（上限为剩余可退运费）
     *
     * 券不作用于运费；未发货订单可分多笔仅退款，故运费同样需要上限约束，
     * 否则首笔退全额运费、后续笔重复退还。
     */
    private function calculateRefundFreight(): string
    {
        $remainingFreight = $this->order->getRemainingRefundableFreight();

        if (bccomp($remainingFreight, '0', 2) !== 1) {
            return '0.00';
        }

        // 仅退款默认退剩余全额运费；退货退款按申请金额，二者均不超过剩余可退运费
        $requested = $this->data->type === RefundType::ReturnRefund
            ? number_format($this->data->freightAmount, 2, '.', '')
            : $remainingFreight;

        return bccomp($requested, $remainingFreight, 2) === 1 ? $remainingFreight : $requested;
    }

    /**
     * 兜底校验：任何退款组合总额不得超过订单实付金额
     *
     * @param  string  $total  本笔退款总额
     *
     * @throws InvalidArgumentException 退款总额超出实付金额
     */
    private function assertRefundWithinPaid(string $total): void
    {
        $paid = number_format($this->order->getTotalAmount(), 2, '.', '');

        $refunded = $this->order->refunds()
            ->whereIn('status', RefundStatus::effectiveCases())
            ->sum('total');

        $sum = bcadd(number_format((float) $refunded, 2, '.', ''), $total, 2);

        if (bccomp($sum, $paid, 2) === 1) {
            throw new InvalidArgumentException('退款总额超出订单实付金额，无法创建退款单');
        }
    }
}
