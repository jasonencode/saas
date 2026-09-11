<?php

namespace App\Services\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\Refundable;
use App\Contracts\ServiceInterface;
use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderLogAction;
use App\Enums\Mall\OrderStatus;
use App\Enums\Mall\RefundExpressStatus;
use App\Enums\Mall\RefundLogAction;
use App\Enums\Mall\RefundStatus;
use App\Enums\Mall\RefundType;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use App\Models\Mall\Refund;
use App\Models\Mall\RefundItem;
use App\Services\Campaign\CouponService;
use App\Services\Mall\DTOs\RefundData;
use App\Services\Mall\DTOs\RefundItemData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class RefundService implements ServiceInterface
{
    /**
     * 创建退款申请
     *
     * @param  Order  $order  订单
     * @param  Authenticatable  $user  用户
     * @param  RefundData  $data  退款数据（已校验）
     *
     * @throws Throwable 订单不可退款或数据验证失败
     *
     * @return Refund 创建的退款单
     */
    public function createRefund(Order $order, Authenticatable $user, RefundData $data): Refund
    {
        $this->validateOrderForRefund($order);
        $this->validateRefundType($order, $data->type);
        $this->validateRefundItems($order, $data->items);

        $amounts = $this->calculateRefundAmount($order, $data);

        return DB::transaction(function () use ($order, $user, $data, $amounts) {
            $refund = Refund::create([
                'tenant_id' => $order->tenant_id,
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'total' => $amounts['total'],
                'goods_amount' => $amounts['goods_amount'],
                'freight_amount' => $amounts['freight_amount'],
                'status' => RefundStatus::Pending,
                'type' => $data->type,
                'reason' => $data->reason,
                'reason_detail' => $data->reasonDetail,
            ]);

            foreach ($data->items as $item) {
                $orderItem = $order->items()->find($item->orderItemId);

                $refund->items()->create([
                    'order_item_id' => $item->orderItemId,
                    'qty' => $item->qty,
                    'price' => $orderItem->price,
                    'remark' => $item->remark,
                ]);
            }

            $this->log(
                refund: $refund,
                action: RefundLogAction::Created,
                user: $user,
                remark: '提交退款申请',
                context: [
                    'type' => $data->type->value,
                    'reason' => $data->reason->value,
                    'items_count' => count($data->items),
                    'total' => $amounts['total'],
                ],
            );

            service(OrderService::class)
                ->log(
                    order: $order,
                    action: OrderLogAction::RefundCreated,
                    user: $user,
                    remark: '提交退款申请',
                    context: [
                        'refund_no' => $refund->no,
                        'refund_amount' => $amounts['total'],
                    ]
                );

            // 未发货订单自动审核通过，直接进入退款处理
            if (!$this->needsReturn($order)) {
                $refund->update([
                    'status' => RefundStatus::Processing,
                    'approved_at' => now(),
                ]);

                $this->log(
                    refund: $refund,
                    action: RefundLogAction::Approved,
                    user: $user,
                    remark: '系统自动审核通过',
                    context: [
                        'previous_status' => RefundStatus::Pending->value,
                        'next_status' => RefundStatus::Processing->value,
                        'auto_approved' => true,
                    ],
                );

                $this->log(
                    refund: $refund,
                    action: RefundLogAction::Processing,
                    user: $user,
                    remark: '自动进入退款处理',
                    context: [
                        'status' => RefundStatus::Processing->value,
                    ],
                );
            }

            return $refund;
        });
    }

    /**
     * 验证订单是否可退款
     *
     * @param  Order  $order  订单
     *
     * @throws InvalidArgumentException 订单状态不支持退款或已有待处理退款
     */
    public function validateOrderForRefund(Order $order): void
    {
        $allowedStatuses = [
            OrderStatus::Paid,
            OrderStatus::Preparing,
            OrderStatus::PartiallyShipped,
            OrderStatus::Delivered,
            OrderStatus::Signed,
        ];

        if (!in_array($order->status, $allowedStatuses, true)) {
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
     * 获取订单允许的退款类型
     *
     * 综合订单状态与履约方式判断，用于 API 响应与校验。
     * - 虚拟商品：仅支持仅退款（无实物可退）
     * - 已发货：仅支持退货退款（已发货不能仅退款）
     * - 未发货：两种均支持
     *
     * @return RefundType[]
     */
    public function getAllowedRefundTypes(Order $order): array
    {
        $allowedStatuses = [
            OrderStatus::Paid,
            OrderStatus::Preparing,
            OrderStatus::PartiallyShipped,
            OrderStatus::Delivered,
            OrderStatus::Signed,
        ];

        if (!in_array($order->status, $allowedStatuses, true)) {
            return [];
        }

        $types = RefundType::cases();

        if ($order->fulfillment_type === FulfillmentType::Virtual) {
            return array_values(array_filter(
                $types,
                static fn (RefundType $t): bool => $t === RefundType::OnlyRefund,
            ));
        }

        $shippedStatuses = [
            OrderStatus::Delivered,
            OrderStatus::Signed,
            OrderStatus::Completed,
        ];

        if (in_array($order->status, $shippedStatuses, true)) {
            return array_values(array_filter(
                $types,
                static fn (RefundType $t): bool => $t === RefundType::ReturnRefund,
            ));
        }

        return $types;
    }

    /**
     * 验证退款类型与订单状态的匹配性
     *
     * @throws InvalidArgumentException
     */
    private function validateRefundType(Order $order, RefundType|string $type): void
    {
        $resolved = $type instanceof RefundType ? $type : RefundType::from($type);

        if (!in_array($resolved, $this->getAllowedRefundTypes($order), true)) {
            $shippedStatuses = [
                OrderStatus::Delivered,
                OrderStatus::Signed,
                OrderStatus::Completed,
            ];

            if ($resolved === RefundType::OnlyRefund && in_array($order->status, $shippedStatuses, true)) {
                throw new InvalidArgumentException('已发货的订单不支持仅退款，请选择退货退款');
            }

            if ($resolved === RefundType::ReturnRefund && $order->fulfillment_type === FulfillmentType::Virtual) {
                throw new InvalidArgumentException('虚拟商品不支持退货退款');
            }

            throw new InvalidArgumentException('当前订单状态不支持该退款类型');
        }
    }

    /**
     * 验证退款商品
     *
     * @param  Order  $order  订单
     * @param  RefundItemData[]  $items  退款商品列表
     *
     * @throws InvalidArgumentException 商品不属于当前订单或可退数量不足
     */
    private function validateRefundItems(Order $order, array $items): void
    {
        $orderItemIds = $order->items()->pluck('id')->toArray();

        foreach ($items as $item) {
            if (!in_array($item->orderItemId, $orderItemIds, true)) {
                throw new InvalidArgumentException('退款商品不属于当前订单');
            }

            $orderItem = $order->items()->find($item->orderItemId);

            $refundableQty = $orderItem->getMaxRefundCounts();

            if ($item->qty > $refundableQty) {
                throw new InvalidArgumentException(
                    "商品「{$orderItem->orderable->getOrderableName()}」可退数量为 {$refundableQty}，退款数量不能超过可退数量"
                );
            }
        }
    }

    /**
     * 计算退款金额
     *
     * 商品金额以订单项真实单价 × 退款数量计算（不信任客户端传入的价格），
     * 并按订单券抵扣比例分摊到订单项，保证任何退款组合总额不超过实付金额；
     * 运费按退款类型处理，上限为「订单运费 − Σ已退运费」：
     * - 仅退款（未发货订单）：退还剩余可退运费（未发货订单可分多笔退款，避免重复退全额运费）
     * - 退货退款（已发货订单）：按申请退运费退还
     *
     * @param  Order  $order  订单
     * @param  RefundData  $data  退款数据（含商品列表、类型、申请退运费）
     *
     * @throws InvalidArgumentException 退款总额超出订单实付金额
     *
     * @return array{goods_amount: string, freight_amount: string, total: string} 退款金额明细
     */
    private function calculateRefundAmount(Order $order, RefundData $data): array
    {
        $orderItems = $order->items()->get()->keyBy('id');
        $netAmounts = $this->netItemAmounts($order, $orderItems);

        $goodsAmount = '0.00';

        foreach ($data->items as $item) {
            $orderItem = $orderItems->get($item->orderItemId);
            $netAmount = $netAmounts[$item->orderItemId] ?? '0.00';
            $unitPrice = $this->netUnitPrice($netAmount, $orderItem->qty);

            // 剩余可退 = 分摊后可退金额 − 已退金额（按同口径累计），兜住单价尾差
            $refundedQty = $this->refundedQtyOf($item->orderItemId);
            $remainingAmount = bcsub($netAmount, bcmul($unitPrice, (string) $refundedQty, 2), 2);

            $itemAmount = bcmul($unitPrice, (string) $item->qty, 2);
            if (bccomp($itemAmount, $remainingAmount, 2) === 1) {
                $itemAmount = $remainingAmount;
            }

            $goodsAmount = bcadd($goodsAmount, $itemAmount, 2);
        }

        $freightAmount = $this->calculateRefundFreight($order, $data);
        $total = bcadd($goodsAmount, $freightAmount, 2);

        $this->assertRefundWithinPaid($order, $total);

        return [
            'goods_amount' => $goodsAmount,
            'freight_amount' => $freightAmount,
            'total' => $total,
        ];
    }

    /**
     * 计算各订单项分摊抵扣后的可退金额
     *
     * 读取下单时落库的分摊快照（`order_items.coupon_discount`，由
     * `CouponService::apportionDiscount()` 写入），不重新计算，保证与展示口径一致。
     *
     * @param  Order  $order  订单
     * @param  Collection<int, OrderItem>  $orderItems  订单项
     *
     * @return array<int, string> 订单项 ID => 分摊后可退金额
     */
    private function netItemAmounts(Order $order, Collection $orderItems): array
    {
        $amounts = [];

        foreach ($orderItems as $orderItem) {
            $subtotal = bcmul((string) $orderItem->price, (string) $orderItem->qty, 2);
            $share = number_format((float) $orderItem->coupon_discount, 2, '.', '');

            $amounts[$orderItem->getKey()] = bcsub($subtotal, $share, 2);
        }

        return $amounts;
    }

    /**
     * 计算分摊后的可退单价（保留 2 位）
     *
     * @param  string  $netAmount  订单项分摊后可退金额
     * @param  int  $qty  订单项数量
     *
     * @return string 分摊后单价
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
     *
     * @param  int  $orderItemId  订单项 ID
     */
    private function refundedQtyOf(int $orderItemId): int
    {
        return (int) RefundItem::query()
            ->where('order_item_id', $orderItemId)
            ->whereHas('refund', fn ($query) => $query->whereIn('status', RefundStatus::effectiveCases()))
            ->sum('qty');
    }

    /**
     * 计算本笔退款运费（上限为剩余可退运费）
     *
     * 券不作用于运费；未发货订单可分多笔仅退款，故运费同样需要上限约束，
     * 否则首笔退全额运费、后续笔重复退还。
     *
     * @param  Order  $order  订单
     * @param  RefundData  $data  退款数据
     *
     * @return string 本笔退款运费
     */
    private function calculateRefundFreight(Order $order, RefundData $data): string
    {
        $orderFreight = number_format((float) $order->freight, 2, '.', '');

        $refundedFreight = $order->refunds()
            ->whereIn('status', RefundStatus::effectiveCases())
            ->sum('freight_amount');

        $remainingFreight = bcsub($orderFreight, number_format((float) $refundedFreight, 2, '.', ''), 2);

        if (bccomp($remainingFreight, '0', 2) !== 1) {
            return '0.00';
        }

        // 仅退款默认退剩余全额运费；退货退款按申请金额，二者均不超过剩余可退运费
        $requested = $data->type === RefundType::ReturnRefund
            ? number_format($data->freightAmount, 2, '.', '')
            : $remainingFreight;

        return bccomp($requested, $remainingFreight, 2) === 1 ? $remainingFreight : $requested;
    }

    /**
     * 兜底校验：任何退款组合总额不得超过订单实付金额
     *
     * @param  Order  $order  订单
     * @param  string  $total  本笔退款总额
     *
     * @throws InvalidArgumentException 退款总额超出实付金额
     */
    private function assertRefundWithinPaid(Order $order, string $total): void
    {
        $paid = number_format($order->getTotalAmount(), 2, '.', '');

        $refunded = $order->refunds()
            ->whereIn('status', RefundStatus::effectiveCases())
            ->sum('total');

        $sum = bcadd(number_format((float) $refunded, 2, '.', ''), $total, 2);

        if (bccomp($sum, $paid, 2) === 1) {
            throw new InvalidArgumentException('退款总额超出订单实付金额，无法创建退款单');
        }
    }

    /**
     * 取消退款
     *
     * @param  Refund  $refund  退款单
     * @param  Authenticatable  $user  用户
     *
     * @throws Throwable 退款单状态不允许取消
     */
    public function cancelRefund(Refund $refund, Authenticatable $user): void
    {
        if ($refund->status !== RefundStatus::Pending) {
            throw new RuntimeException('只能取消待审核的退款');
        }

        DB::transaction(function () use ($refund, $user) {
            $refund->update([
                'status' => RefundStatus::Cancelled,
            ]);

            $this->log(
                refund: $refund,
                action: RefundLogAction::Cancelled,
                user: $user,
                remark: '用户取消退款',
                context: [
                    'previous_status' => RefundStatus::Pending->value,
                ],
            );
        });
    }

    /**
     * 审核通过
     *
     * 根据退款类型和订单状态自动判断下一步流转：
     * - 未发货订单 / 仅退款 → 直接进入退款处理
     * - 已发货且需退货 → 进入等待退货流程
     *
     * @param  Refund  $refund  退款单
     * @param  Authenticatable  $user  审核人
     * @param  string|null  $remark  审核备注
     *
     * @throws Throwable 退款单状态不允许审核
     */
    public function approveRefund(Refund $refund, Authenticatable $user, ?string $remark = null): void
    {
        if ($refund->status !== RefundStatus::Pending) {
            throw new RuntimeException('只能审核待审核的退款');
        }

        $order = $refund->order;
        $needsReturn = $this->needsReturn($order);

        DB::transaction(function () use ($refund, $user, $remark, $needsReturn) {
            if ($needsReturn) {
                $refund->update([
                    'status' => RefundStatus::WaitingReturn,
                    'approved_by' => $user->getKey(),
                    'approved_at' => now(),
                    'approval_remark' => $remark,
                ]);

                $this->log(
                    refund: $refund,
                    action: RefundLogAction::Approved,
                    user: $user,
                    remark: $remark ?? '审核通过',
                    context: [
                        'previous_status' => RefundStatus::Pending->value,
                        'next_status' => RefundStatus::WaitingReturn->value,
                        'needs_return' => true,
                    ],
                );

                $this->log(
                    refund: $refund,
                    action: RefundLogAction::WaitingReturn,
                    user: $user,
                    remark: '等待用户退货',
                    context: [
                        'status' => RefundStatus::WaitingReturn->value,
                    ],
                );
            } else {
                $refund->update([
                    'status' => RefundStatus::Processing,
                    'approved_by' => $user->getKey(),
                    'approved_at' => now(),
                    'approval_remark' => $remark,
                ]);

                $this->log(
                    refund: $refund,
                    action: RefundLogAction::Approved,
                    user: $user,
                    remark: $remark ?? '审核通过',
                    context: [
                        'previous_status' => RefundStatus::Pending->value,
                        'next_status' => RefundStatus::Processing->value,
                        'needs_return' => false,
                    ],
                );

                $this->log(
                    refund: $refund,
                    action: RefundLogAction::Processing,
                    user: $user,
                    remark: '自动进入退款处理',
                    context: [
                        'status' => RefundStatus::Processing->value,
                    ],
                );
            }
        });
    }

    /**
     * 判断退款是否需要退货
     *
     * @param  Order  $order  订单
     *
     * @return bool 是否需要退货
     */
    private function needsReturn(Order $order): bool
    {
        $noShippedStatuses = [OrderStatus::Paid, OrderStatus::Preparing];
        if (in_array($order->status, $noShippedStatuses, true)) {
            return false;
        }

        return true;
    }

    /**
     * 审核驳回
     *
     * @param  Refund  $refund  退款单
     * @param  Authenticatable  $user  审核人
     * @param  string  $remark  驳回原因
     *
     * @throws Throwable 退款单状态不允许审核
     */
    public function rejectRefund(Refund $refund, Authenticatable $user, string $remark): void
    {
        if ($refund->status !== RefundStatus::Pending) {
            throw new RuntimeException('只能审核待审核的退款');
        }

        DB::transaction(function () use ($refund, $user, $remark) {
            $refund->update([
                'status' => RefundStatus::Rejected,
                'approved_by' => $user->getKey(),
                'approved_at' => now(),
                'approval_remark' => $remark,
            ]);

            $this->log(
                refund: $refund,
                action: RefundLogAction::Rejected,
                user: $user,
                remark: $remark,
                context: [
                    'previous_status' => RefundStatus::Pending->value,
                    'next_status' => RefundStatus::Rejected->value,
                    'reject_reason' => $remark,
                ],
            );
        });
    }

    /**
     * 用户提交退货物流
     *
     * @param  Refund  $refund  退款单
     * @param  Authenticatable  $user  用户
     * @param  array  $expressData  物流数据（express_id, express_no）
     *
     * @throws Throwable 退款单状态不允许提交物流
     */
    public function shipReturn(Refund $refund, Authenticatable $user, array $expressData): void
    {
        if ($refund->status !== RefundStatus::WaitingReturn) {
            throw new RuntimeException('只能在等待退货状态下提交物流信息');
        }

        DB::transaction(function () use ($refund, $user, $expressData) {
            $refund->express()->updateOrCreate(
                ['refund_id' => $refund->id],
                [
                    'express_id' => $expressData['express_id'],
                    'express_no' => $expressData['express_no'],
                    'status' => RefundExpressStatus::Shipped,
                    'shipped_at' => now(),
                ]
            );

            $refund->update(['status' => RefundStatus::Shipping]);

            $this->log(
                refund: $refund,
                action: RefundLogAction::ReturnShipped,
                user: $user,
                remark: "已发货，物流单号：{$expressData['express_no']}",
                context: [
                    'previous_status' => RefundStatus::WaitingReturn->value,
                    'next_status' => RefundStatus::Shipping->value,
                    'express_id' => $expressData['express_id'],
                    'express_no' => $expressData['express_no'],
                ],
            );
        });
    }

    /**
     * 商户确认签收退货
     *
     * @param  Refund  $refund  退款单
     * @param  Authenticatable  $user  操作人
     * @param  string|null  $remark  备注
     *
     * @throws Throwable 退款单状态不允许确认签收
     */
    public function confirmReceive(Refund $refund, Authenticatable $user, ?string $remark = null): void
    {
        if ($refund->status !== RefundStatus::Shipping) {
            throw new RuntimeException('只能确认退货中的退款单');
        }

        DB::transaction(function () use ($refund, $user, $remark) {
            $refund->express()->update([
                'status' => RefundExpressStatus::Received,
                'received_at' => now(),
            ]);

            $refund->update(['status' => RefundStatus::Processing]);

            $this->log(
                refund: $refund,
                action: RefundLogAction::ReturnReceived,
                user: $user,
                remark: $remark ?? '已签收退货商品',
                context: [
                    'previous_status' => RefundStatus::Shipping->value,
                    'next_status' => RefundStatus::Received->value,
                ],
            );

            $this->log(
                refund: $refund,
                action: RefundLogAction::Processing,
                user: $user,
                remark: '签收后自动进入退款处理',
                context: [
                    'status' => RefundStatus::Processing->value,
                ],
            );
        });
    }

    /**
     * 确认退款（执行退款完成）
     *
     * @param  Refund  $refund  退款单
     * @param  Authenticatable  $user  操作人
     * @param  string|null  $remark  备注
     *
     * @throws Throwable 退款单状态不允许确认退款
     */
    public function confirmRefund(Refund $refund, Authenticatable $user, ?string $remark = null): void
    {
        if ($refund->status !== RefundStatus::Processing) {
            throw new RuntimeException('只能确认退款处理中的退款单');
        }

        DB::transaction(function () use ($refund, $user, $remark) {
            $refund->update([
                'status' => RefundStatus::Completed,
                'refund_at' => now(),
            ]);

            $this->log(
                refund: $refund,
                action: RefundLogAction::Completed,
                user: $user,
                remark: $remark ?? '退款完成',
                context: [
                    'previous_status' => RefundStatus::Processing->value,
                    'next_status' => RefundStatus::Completed->value,
                    'refund_at' => now()->toDateTimeString(),
                ],
            );

            // 退款资源回收，委托给可订购主体的 Refundable 实现
            $refund->loadMissing('items.orderItem.orderable');
            foreach ($refund->items as $refundItem) {
                $orderable = $refundItem->orderItem?->orderable;
                if ($orderable instanceof Refundable) {
                    $orderable->refund($refundItem, $refundItem->qty);
                }
            }

            // 检查是否全部商品已退款，更新订单状态
            $order = $refund->order;
            $allRefunded = $this->allItemsRefunded($order);

            $this->updateOrderStatusAfterRefund($order, $allRefunded);

            // 全部退款完成：返还订单占用的优惠券（部分退款不返还，券作用于整单基数）
            // 退款已按实付口径分摊并执行，故保留订单 coupon_discount 历史快照，不归零
            if ($allRefunded) {
                service(CouponService::class)->releaseFromRefundedOrder($order);
            }
        });
    }

    /**
     * 重试退款（处理退款失败重试）
     *
     * 仅支持 Failed 状态的退款单重试，重试后状态回到 Processing。
     *
     * @param  Refund  $refund  退款单
     * @param  Authenticatable  $user  操作人
     * @param  string|null  $remark  备注
     *
     * @throws Throwable 退款单状态不允许重试
     */
    public function retryRefund(Refund $refund, Authenticatable $user, ?string $remark = null): void
    {
        if ($refund->status !== RefundStatus::Failed) {
            throw new RuntimeException('只能重试退款失败的退款单');
        }

        DB::transaction(function () use ($refund, $user, $remark) {
            $refund->update([
                'status' => RefundStatus::Processing,
            ]);

            $this->log(
                refund: $refund,
                action: RefundLogAction::Processing,
                user: $user,
                remark: $remark ?? '重试退款处理',
                context: [
                    'previous_status' => RefundStatus::Failed->value,
                    'next_status' => RefundStatus::Processing->value,
                ],
            );
        });
    }

    /**
     * 标记退款失败
     *
     * 将 Processing 状态的退款单标记为失败。
     *
     * @param  Refund  $refund  退款单
     * @param  Authenticatable  $user  操作人
     * @param  string  $remark  失败原因
     *
     * @throws Throwable 退款单状态不允许标记失败
     */
    public function markRefundFailed(Refund $refund, Authenticatable $user, string $remark): void
    {
        if ($refund->status !== RefundStatus::Processing) {
            throw new RuntimeException('只能标记退款处理中的退款单为失败');
        }

        DB::transaction(function () use ($refund, $user, $remark) {
            $refund->update([
                'status' => RefundStatus::Failed,
            ]);

            $this->log(
                refund: $refund,
                action: RefundLogAction::Failed,
                user: $user,
                remark: $remark,
                context: [
                    'previous_status' => RefundStatus::Processing->value,
                    'next_status' => RefundStatus::Failed->value,
                    'fail_reason' => $remark,
                ],
            );
        });
    }

    /**
     * 判断订单商品是否已全部退款
     *
     * @param  Order  $order  订单
     *
     * @return bool 全部订单项数量均已退完
     */
    private function allItemsRefunded(Order $order): bool
    {
        $completedRefunds = $order->refunds()
            ->where('status', RefundStatus::Completed)
            ->with('items')
            ->get();

        foreach ($order->items as $orderItem) {
            $refundedQty = 0;
            foreach ($completedRefunds as $refund) {
                foreach ($refund->items as $refundItem) {
                    if ($refundItem->order_item_id === $orderItem->id) {
                        $refundedQty += $refundItem->qty;
                    }
                }
            }

            if ($refundedQty < $orderItem->qty) {
                return false;
            }
        }

        return true;
    }

    /**
     * 退款完成后更新订单状态
     *
     * @param  Order  $order  订单
     * @param  bool  $allRefunded  是否全部商品已退款
     */
    private function updateOrderStatusAfterRefund(Order $order, bool $allRefunded): void
    {
        if (!$allRefunded) {
            return;
        }

        if ($order->status === OrderStatus::Signed) {
            $order->update(['status' => OrderStatus::Completed]);
        } else {
            $order->update([
                'status' => OrderStatus::Signed,
                'signed_at' => $order->signed_at ?? now(),
            ]);
        }
    }

    /**
     * 记录退款日志
     *
     * @param  Refund  $refund  退款单
     * @param  RefundLogAction  $action  操作类型
     * @param  Authenticatable  $user  操作人
     * @param  string  $remark  操作备注
     * @param  array  $context  操作上下文
     */
    private function log(
        Refund $refund,
        RefundLogAction $action,
        Authenticatable $user,
        string $remark,
        array $context = []
    ): void {
        $refund->logs()->create([
            'action' => $action,
            'operator' => $user,
            'remark' => $remark,
            'context' => $context,
        ]);
    }

    /**
     * 判断订单是否可退款
     *
     * @param  Order  $order  订单
     *
     * @return bool 是否可退款
     */
    public function isOrderRefundable(Order $order): bool
    {
        try {
            $this->validateOrderForRefund($order);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
