<?php

namespace App\Ai\Tools;

use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Enums\Mall\RefundStatus;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use App\Models\Mall\Refund;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetTodayOrderStats implements Tool
{
    /**
     * @param  int|null  $tenantId  租户 ID；为空时统计全平台（仅运营端使用）
     */
    public function __construct(protected ?int $tenantId = null) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return '获取今日订单统计：下单量、商品件数、各状态与履约方式分布、今日支付订单数与实付金额、客单价、今日退款金额与退款率、取消率、待发货与待自提数量。用户询问「今天订单怎么样 / 今日概况 / 今日数据 / 日报」时使用，无需参数。';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $today = today();

        $createdQuery = $this->orderQuery()->whereDate('created_at', $today);

        $statusCounts = (clone $createdQuery)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $fulfillmentCounts = (clone $createdQuery)
            ->selectRaw('fulfillment_type, count(*) as total')
            ->groupBy('fulfillment_type')
            ->pluck('total', 'fulfillment_type');

        $paidQuery = $this->orderQuery()->whereDate('paid_at', $today);

        $paidOrders = (clone $paidQuery)->count();

        $paidAmount = (float) (clone $paidQuery)
            ->selectRaw('coalesce(sum(amount - coupon_discount + freight), 0) as total')
            ->value('total');

        $refundQuery = $this->refundQuery()
            ->whereDate('refund_at', $today)
            ->where('status', RefundStatus::Completed);

        $refundOrders = (clone $refundQuery)->count();

        $refundAmount = (float) (clone $refundQuery)->sum('total');

        $itemsQuantity = (int) OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNull('orders.deleted_at')
            ->whereDate('orders.created_at', $today)
            ->when($this->tenantId !== null, fn (Builder $query) => $query->where('orders.tenant_id', $this->tenantId))
            ->sum('order_items.qty');

        $created = (clone $createdQuery)->count();
        $canceled = (int) ($statusCounts[OrderStatus::Canceled->value] ?? 0);

        return (string) json_encode([
            'date' => $today->toDateString(),
            'orders' => [
                'created' => $created,
                'items_quantity' => $itemsQuantity,
                'canceled' => $canceled,
                'cancel_rate' => $created > 0 ? round($canceled / $created * 100, 2) : 0,
                'by_status' => $this->statusSummary($statusCounts),
                'by_fulfillment' => $this->fulfillmentSummary($fulfillmentCounts),
            ],
            'payment' => [
                'paid_orders' => $paidOrders,
                'paid_amount' => round($paidAmount, 2),
                'avg_order_amount' => $paidOrders > 0 ? round($paidAmount / $paidOrders, 2) : 0,
            ],
            'refund' => [
                'completed_orders' => $refundOrders,
                'amount' => round($refundAmount, 2),
                'rate' => $paidAmount > 0 ? round($refundAmount / $paidAmount * 100, 2) : 0,
            ],
            'pending' => [
                'shipment' => $this->sumStatuses($statusCounts),
                'pickup' => (int) ($statusCounts[OrderStatus::PickupPending->value] ?? 0),
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    /**
     * 订单查询，已按当前租户过滤
     */
    private function orderQuery(): Builder
    {
        return Order::query()
            ->when($this->tenantId !== null, fn (Builder $query) => $query->where('tenant_id', $this->tenantId));
    }

    /**
     * 退款查询，已按当前租户过滤
     */
    private function refundQuery(): Builder
    {
        return Refund::query()
            ->when($this->tenantId !== null, fn (Builder $query) => $query->where('tenant_id', $this->tenantId));
    }

    /**
     * @param  Collection<string, int>  $counts
     *
     * @return array<string, int>
     */
    private function statusSummary(Collection $counts): array
    {
        return collect(OrderStatus::cases())
            ->mapWithKeys(fn (OrderStatus $status) => [
                $status->getLabel() => (int) ($counts[$status->value] ?? 0),
            ])
            ->all();
    }

    /**
     * @param  Collection<string, int>  $counts
     *
     * @return array<string, int>
     */
    private function fulfillmentSummary(Collection $counts): array
    {
        return collect(FulfillmentType::cases())
            ->mapWithKeys(fn (FulfillmentType $type) => [
                $type->getLabel() => (int) ($counts[$type->value] ?? 0),
            ])
            ->all();
    }

    /**
     * @param  Collection<string, int>  $counts
     */
    private function sumStatuses(Collection $counts): int
    {
        $statuses = [
            OrderStatus::Paid,
            OrderStatus::Preparing,
            OrderStatus::PartiallyShipped,
        ];

        return collect($statuses)->sum(fn (OrderStatus $status) => (int) ($counts[$status->value] ?? 0));
    }
}
