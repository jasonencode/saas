<?php

namespace App\Http\Resources\Mall;

use App\Enums\Mall\OrderStatus;
use App\Enums\Mall\RefundStatus;
use App\Http\Resources\EnumResource;
use App\Models\Mall\Order;
use App\Models\Mall\Refund;
use App\Services\Mall\RefundService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * 订单售后信息
 *
 * 订单生命周期与退款生命周期正交，通过此对象将售后状态附加到订单响应中。
 * 依赖 Order 的 refunds 关系已 eager-load，否则会触发懒加载。
 */
class AfterSalesResource extends JsonResource
{
    /**
     * 可退款的订单状态集合（与 RefundService::getAllowedRefundTypes 一致）
     *
     * @var OrderStatus[]
     */
    private const array ALLOWED_STATUSES = [
        OrderStatus::Paid,
        OrderStatus::Preparing,
        OrderStatus::PartiallyShipped,
        OrderStatus::Delivered,
        OrderStatus::Signed,
    ];

    /**
     * 转换为数组格式
     *
     * @return array{active: array<string, mixed>|null, can_apply: bool, can_apply_types: array<string>, refunded_total: string}
     */
    public function toArray(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;

        /** @var Collection<int, Refund> $refunds */
        $refunds = $order->refunds ?? collect();

        // 1. 订单状态不支持退款，直接返回（跳过 active 搜索和类型计算）
        if (!in_array($order->status, self::ALLOWED_STATUSES, true)) {
            return $this->buildResponse(null, false, [], $refunds);
        }

        // 2. 查找最新活跃退款（非终态）
        $activeStatuses = RefundStatus::activeCases();
        $active = $refunds
            ->sortByDesc('id')
            ->first(static fn (Refund $r): bool => in_array($r->status, $activeStatuses, true));

        $canApply = $active === null;

        // 3. 仅在可申请时才解析服务（省掉一次服务解析）
        $canApplyTypes = $canApply
            ? collect(service(RefundService::class)->getAllowedRefundTypes($order))
                ->map(static fn ($type): string => $type->value)
                ->all()
            : [];

        return $this->buildResponse($active, $canApply, $canApplyTypes, $refunds);
    }

    /**
     * 构建统一响应结构
     *
     * @param  Refund|null  $active  活跃退款
     * @param  bool  $canApply  是否可申请
     * @param  string[]  $canApplyTypes  允许的退款类型值
     * @param  Collection<int, Refund>  $refunds  退款集合
     */
    private function buildResponse(?Refund $active, bool $canApply, array $canApplyTypes, $refunds): array
    {
        return [
            'active' => $active ? [
                'refund_id' => $active->id,
                'no' => $active->no,
                'status' => EnumResource::make($active->status),
                'type' => EnumResource::make($active->type),
                'total' => $active->total,
            ] : null,
            'can_apply' => $canApply,
            'can_apply_types' => $canApplyTypes,
            'refunded_total' => $refunds
                ->filter(static fn (Refund $r): bool => $r->status === RefundStatus::Completed)
                ->reduce(static fn (string $carry, Refund $r): string => bcadd($carry, $r->total, 2), '0.00'),
        ];
    }
}
