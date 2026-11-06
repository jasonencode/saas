<?php

namespace App\Ai\Tools;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetPendingFulfillment implements Tool
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
        return '履约积压：统计当前待发货（待发货/备货中/部分发货）与待自提订单的数量、金额、等待时长分布（24 小时内 / 24-72 小时 / 超过 72 小时），并列出等待最久的前 N 笔订单。用户询问「有多少单没发货 / 发货积压 / 超时未发货 / 待自提订单」时使用。';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $limit = max(1, min(50, $request->integer('limit') ?: 10));

        return (string) json_encode([
            'generated_at' => now()->toDateTimeString(),
            'shipment' => $this->summarize([
                OrderStatus::Paid,
                OrderStatus::Preparing,
                OrderStatus::PartiallyShipped,
            ], $limit),
            'pickup' => $this->summarize([
                OrderStatus::PickupPending,
            ], $limit),
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()->description('每类积压返回的最久等待订单条数，默认 10，最大 50'),
        ];
    }

    /**
     * 汇总某一积压状态集合的数量、金额、等待时长与最久订单
     *
     * @param  array<int, OrderStatus>  $statuses
     *
     * @return array<string, mixed>
     */
    private function summarize(array $statuses, int $limit): array
    {
        $query = $this->orderQuery()
            ->whereIn('status', array_map(fn (OrderStatus $status) => $status->value, $statuses));

        $count = (clone $query)->count();

        $amount = (float) (clone $query)
            ->selectRaw('coalesce(sum(amount - coupon_discount + freight), 0) as total')
            ->value('total');

        $within24 = (clone $query)->where('paid_at', '>=', now()->subHours(24))->count();
        $within72 = (clone $query)->where('paid_at', '>=', now()->subHours(72))->count();

        $oldest = (clone $query)
            ->orderBy('paid_at')
            ->limit($limit)
            ->get(['no', 'status', 'paid_at', 'amount', 'coupon_discount', 'freight']);

        return [
            'count' => $count,
            'amount' => round($amount, 2),
            'age' => [
                'within_24h' => $within24,
                'between_24h_72h' => max(0, $within72 - $within24),
                'over_72h' => max(0, $count - $within72),
            ],
            'oldest' => $oldest->map(fn (Order $order) => [
                'order_no' => $order->no,
                'status' => $order->status->getLabel(),
                'paid_at' => $order->paid_at?->toDateTimeString(),
                'waiting_hours' => $order->paid_at
                    ? round(abs($order->paid_at->diffInMinutes(now())) / 60, 1)
                    : null,
                'amount' => round(
                    (float) $order->amount - (float) $order->coupon_discount + (float) $order->freight,
                    2
                ),
            ])->values()->all(),
        ];
    }

    /**
     * 订单查询，已按当前租户过滤
     */
    private function orderQuery(): Builder
    {
        return Order::query()
            ->when($this->tenantId !== null, fn (Builder $query) => $query->where('tenant_id', $this->tenantId));
    }
}
