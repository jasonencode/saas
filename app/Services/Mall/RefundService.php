<?php

namespace App\Services\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\ServiceInterface;
use App\Enums\Mall\DeductStockType;
use App\Enums\Mall\OrderStatus;
use App\Enums\Mall\RefundExpressStatus;
use App\Enums\Mall\RefundLogAction;
use App\Enums\Mall\RefundStatus;
use App\Enums\Mall\RefundType;
use App\Models\Mall\Order;
use App\Models\Mall\Refund;
use App\Notifications\Mall\RefundApprovedNotification;
use App\Notifications\Mall\RefundCompletedNotification;
use App\Notifications\Mall\RefundRejectedNotification;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class RefundService implements ServiceInterface
{
    /**
     * 创建退款申请
     *
     * @throws Throwable
     */
    public function createRefund(Order $order, Authenticatable $user, array $data): Refund
    {
        $this->validateOrderForRefund($order);
        $this->validateRefundType($order, $data['type']);
        $this->validateRefundItems($order, $data['items']);

        $amounts = $this->calculateRefundAmount($data['items'], $data['type']);

        return DB::transaction(function() use ($order, $user, $data, $amounts) {
            $refund = Refund::create([
                'tenant_id' => $order->tenant_id,
                'order_id' => $order->id,
                'total' => $amounts['total'],
                'goods_amount' => $amounts['goods_amount'],
                'freight_amount' => $amounts['freight_amount'],
                'status' => RefundStatus::Pending,
                'type' => $data['type'],
                'reason' => $data['reason'],
                'reason_detail' => $data['reason_detail'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $refund->items()->create([
                    'order_item_id' => $item['order_item_id'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                ]);
            }

            $refund->logs()->create([
                'action' => RefundLogAction::Created,
                'operator_id' => $user->id,
                'remark' => '提交退款申请',
            ]);

            return $refund;
        });
    }

    /**
     * 取消退款
     *
     * @throws Throwable
     */
    public function cancelRefund(Refund $refund, Authenticatable $user): void
    {
        if ($refund->status !== RefundStatus::Pending) {
            throw new RuntimeException('只能取消待审核的退款');
        }

        DB::transaction(function() use ($refund, $user) {
            $refund->update([
                'status' => RefundStatus::Cancelled,
            ]);

            $refund->logs()->create([
                'action' => RefundLogAction::Cancelled,
                'operator_id' => $user->id,
                'remark' => '用户取消退款',
            ]);
        });
    }

    /**
     * 审核通过
     *
     * 根据退款类型和订单状态自动判断下一步流转：
     * - 未发货订单 / 仅退款 → 直接进入退款处理
     * - 已发货且需退货 → 进入等待退货流程
     *
     * @throws Throwable
     */
    public function approveRefund(Refund $refund, Authenticatable $user, ?string $remark = null): void
    {
        if ($refund->status !== RefundStatus::Pending) {
            throw new RuntimeException('只能审核待审核的退款');
        }

        $order = $refund->order;
        $needsReturn = $this->needsReturn($refund, $order);

        DB::transaction(function() use ($refund, $user, $remark, $needsReturn) {
            if ($needsReturn) {
                $refund->update([
                    'status' => RefundStatus::WaitingReturn,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'approval_remark' => $remark,
                ]);

                $refund->logs()->create([
                    'action' => RefundLogAction::Approved,
                    'operator_id' => $user->id,
                    'remark' => $remark ?? '审核通过',
                ]);
            } else {
                $refund->update([
                    'status' => RefundStatus::Processing,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'approval_remark' => $remark,
                ]);

                $refund->logs()->create([
                    'action' => RefundLogAction::Approved,
                    'operator_id' => $user->id,
                    'remark' => $remark ?? '审核通过',
                ]);

                $refund->logs()->create([
                    'action' => RefundLogAction::Processing,
                    'operator_id' => $user->id,
                    'remark' => '自动进入退款处理',
                ]);
            }
        });

        DB::afterCommit(fn () => $refund->tenant->notify(new RefundApprovedNotification($refund)));
    }

    /**
     * 审核驳回
     *
     * @throws Throwable
     */
    public function rejectRefund(Refund $refund, Authenticatable $user, string $remark): void
    {
        if ($refund->status !== RefundStatus::Pending) {
            throw new RuntimeException('只能审核待审核的退款');
        }

        DB::transaction(function() use ($refund, $user, $remark) {
            $refund->update([
                'status' => RefundStatus::Rejected,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'approval_remark' => $remark,
            ]);

            $refund->logs()->create([
                'action' => RefundLogAction::Rejected,
                'operator_id' => $user->id,
                'remark' => $remark,
            ]);
        });

        DB::afterCommit(fn () => $refund->tenant->notify(new RefundRejectedNotification($refund, $remark)));
    }

    /**
     * 用户提交退货物流
     *
     * @throws Throwable
     */
    public function shipReturn(Refund $refund, Authenticatable $user, array $expressData): void
    {
        if ($refund->status !== RefundStatus::WaitingReturn) {
            throw new RuntimeException('只能在等待退货状态下提交物流信息');
        }

        DB::transaction(function() use ($refund, $user, $expressData) {
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

            $refund->logs()->create([
                'action' => RefundLogAction::ReturnShipped,
                'operator_id' => $user->id,
                'remark' => "已发货，物流单号：{$expressData['express_no']}",
            ]);
        });
    }

    /**
     * 商户确认签收退货
     *
     * @throws Throwable
     */
    public function confirmReceive(Refund $refund, Authenticatable $user, ?string $remark = null): void
    {
        if ($refund->status !== RefundStatus::Shipping) {
            throw new RuntimeException('只能确认退货中的退款单');
        }

        DB::transaction(function() use ($refund, $user, $remark) {
            $refund->express()->update([
                'status' => RefundExpressStatus::Received,
                'received_at' => now(),
            ]);

            $refund->update(['status' => RefundStatus::Processing]);

            $refund->logs()->create([
                'action' => RefundLogAction::ReturnReceived,
                'operator_id' => $user->id,
                'remark' => $remark ?? '已签收退货商品',
            ]);

            $refund->logs()->create([
                'action' => RefundLogAction::Processing,
                'operator_id' => $user->id,
                'remark' => '签收后自动进入退款处理',
            ]);
        });
    }

    /**
     * 确认退款（执行退款完成）
     *
     * @throws Throwable
     */
    public function confirmRefund(Refund $refund, Authenticatable $user, ?string $remark = null): void
    {
        if ($refund->status !== RefundStatus::Processing) {
            throw new RuntimeException('只能确认退款处理中的退款单');
        }

        DB::transaction(function() use ($refund, $user, $remark) {
            $refund->update([
                'status' => RefundStatus::Completed,
                'refund_at' => now(),
            ]);

            $refund->logs()->create([
                'action' => RefundLogAction::Completed,
                'operator_id' => $user->id,
                'remark' => $remark ?? '退款完成',
            ]);

            // 回退库存（仅下单时扣减库存的商品）
            $refund->loadMissing('items.orderItem.product', 'items.orderItem.sku');
            foreach ($refund->items as $refundItem) {
                $orderItem = $refundItem->orderItem;
                if (!$orderItem || !$orderItem->product) {
                    continue;
                }
                if ($orderItem->product->deduct_stock_type === DeductStockType::Ordered) {
                    if ($orderItem->sku) {
                        $orderItem->sku->increment('stock', $refundItem->qty);
                    } else {
                        $orderItem->product->increment('stock', $refundItem->qty);
                    }
                }
            }

            // 检查是否全部商品已退款，更新订单状态
            $this->updateOrderStatusAfterRefund($refund->order);
        });

        DB::afterCommit(fn () => $refund->tenant->notify(new RefundCompletedNotification($refund)));
    }

    /**
     * 退款完成后更新订单状态
     */
    private function updateOrderStatusAfterRefund(Order $order): void
    {
        $completedRefunds = $order->refunds()
            ->where('status', RefundStatus::Completed)
            ->with('items')
            ->get();

        $orderItems = $order->items;
        $allRefunded = true;

        foreach ($orderItems as $orderItem) {
            $refundedQty = 0;
            foreach ($completedRefunds as $refund) {
                foreach ($refund->items as $refundItem) {
                    if ($refundItem->order_item_id === $orderItem->id) {
                        $refundedQty += $refundItem->qty;
                    }
                }
            }

            if ($refundedQty < $orderItem->qty) {
                $allRefunded = false;
                break;
            }
        }

        if ($allRefunded) {
            if ($order->status === OrderStatus::Signed) {
                $order->update(['status' => OrderStatus::Completed]);
            } else {
                $order->update([
                    'status' => OrderStatus::Signed,
                    'signed_at' => $order->signed_at ?? now(),
                ]);
            }
        }
    }

    /**
     * 判断退款是否需要退货
     */
    private function needsReturn(Refund $refund, Order $order): bool
    {
        $noShippedStatuses = [OrderStatus::Paid, OrderStatus::Preparing];
        if (in_array($order->status, $noShippedStatuses, true)) {
            return false;
        }

        return true;
    }

    /**
     * 判断订单是否可退款
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

    /**
     * 验证订单是否可退款
     *
     * @throws Throwable
     */
    public function validateOrderForRefund(Order $order): void
    {
        $allowedStatuses = [
            OrderStatus::Paid,
            OrderStatus::Preparing,
            OrderStatus::PartiallyShipped,
            OrderStatus::Delivered,
            OrderStatus::Signed,
            OrderStatus::Completed,
        ];

        if (!in_array($order->status, $allowedStatuses, true)) {
            throw new InvalidArgumentException('当前订单状态不支持退款');
        }

        $hasPendingRefund = $order->refunds()
            ->whereIn('status', [
                RefundStatus::Pending,
                RefundStatus::WaitingReturn,
                RefundStatus::Shipping,
                RefundStatus::Received,
                RefundStatus::Processing,
            ])
            ->exists();

        if ($hasPendingRefund) {
            throw new RuntimeException('已有退款申请正在处理中');
        }
    }

    /**
     * 验证退款类型与订单状态的匹配性
     *
     * @throws InvalidArgumentException
     */
    private function validateRefundType(Order $order, RefundType|string $type): void
    {
        $shippedStatuses = [
            OrderStatus::Delivered,
            OrderStatus::Signed,
            OrderStatus::Completed,
        ];

        if ($type === RefundType::OnlyRefund && in_array($order->status, $shippedStatuses, true)) {
            throw new InvalidArgumentException('已发货的订单不支持仅退款，请选择退货退款');
        }
    }

    /**
     * 验证退款商品
     */
    private function validateRefundItems(Order $order, array $items): void
    {
        $orderItemIds = $order->items()->pluck('id')->toArray();

        $activeStatuses = [
            RefundStatus::Pending,
            RefundStatus::WaitingReturn,
            RefundStatus::Shipping,
            RefundStatus::Received,
            RefundStatus::Processing,
        ];

        foreach ($items as $item) {
            if (!in_array($item['order_item_id'], $orderItemIds)) {
                throw new InvalidArgumentException('退款商品不属于当前订单');
            }

            $orderItem = $order->items()->find($item['order_item_id']);

            $refundedQty = $orderItem->refundItems()
                ->whereHas('refund', fn($q) => $q->whereIn('status', $activeStatuses))
                ->sum('qty');

            $refundableQty = $orderItem->qty - $refundedQty;

            if ($item['qty'] > $refundableQty) {
                throw new InvalidArgumentException(
                    "商品「{$orderItem->product_name}」可退数量为 {$refundableQty}，退款数量不能超过可退数量"
                );
            }
        }
    }

    /**
     * 计算退款金额
     */
    private function calculateRefundAmount(array $items, RefundType|string $type): array
    {
        $goodsAmount = 0;
        foreach ($items as $item) {
            $goodsAmount = bcadd($goodsAmount, bcmul($item['qty'], $item['price'], 2), 2);
        }

        $freightAmount = '0.00';

        return [
            'goods_amount' => $goodsAmount,
            'freight_amount' => $freightAmount,
            'total' => bcadd($goodsAmount, $freightAmount, 2),
        ];
    }
}
