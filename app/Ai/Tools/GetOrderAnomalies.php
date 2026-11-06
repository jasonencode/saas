<?php

namespace App\Ai\Tools;

use App\Ai\Tools\Concerns\InteractsWithOrders;
use App\Ai\Tools\Concerns\UsesOrderStatuses;
use App\Enums\Mall\OrderStatus;
use App\Enums\Mall\RefundStatus;
use App\Models\Mall\Order;
use App\Models\User\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetOrderAnomalies implements Tool
{
    use InteractsWithOrders;
    use UsesOrderStatuses;

    /**
     * @param  int|null  $tenantId  租户 ID；为空时巡检全平台（仅运营端使用）
     */
    public function __construct(protected ?int $tenantId = null) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return '订单异常巡检：长时间停滞在某一状态的订单、退款中仍待发货的订单、同一用户短时间重复下单、0 元成交订单。用户问「有没有异常订单 / 哪些单卡住了 / 有没有刷单」时使用。';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $stalledHours = max(1, min(720, (int) ($request->integer('stalled_hours') ?: 72)));
        $duplicateHours = max(1, min(168, (int) ($request->integer('duplicate_hours') ?: 24)));
        $limit = max(1, min(50, (int) ($request->integer('limit') ?: 10)));

        $activeRefundStatuses = array_map(fn (RefundStatus $status) => $status->value, RefundStatus::activeCases());

        $stalledQuery = $this->orderQuery()
            ->whereIn('status', array_map(fn (OrderStatus $status) => $status->value, [
                OrderStatus::Paid,
                OrderStatus::Preparing,
                OrderStatus::PartiallyShipped,
                OrderStatus::Delivered,
                OrderStatus::Signed,
                OrderStatus::PickupPending,
            ]))
            ->where('updated_at', '<=', now()->subHours($stalledHours));

        $refundingQuery = $this->orderQuery()
            ->whereIn('status', array_map(fn (OrderStatus $status) => $status->value, [
                OrderStatus::Paid,
                OrderStatus::Preparing,
                OrderStatus::PartiallyShipped,
            ]))
            ->whereHas('refunds', fn (Builder $query) => $query->whereIn('status', $activeRefundStatuses));

        $zeroAmountQuery = $this->orderQuery()
            ->whereIn('status', $this->salesStatuses())
            ->whereRaw('amount - coupon_discount + freight <= 0');

        $duplicates = $this->orderQuery()
            ->where('created_at', '>=', now()->subHours($duplicateHours))
            ->where('status', '!=', OrderStatus::Canceled->value)
            ->selectRaw('user_id, count(*) as orders, coalesce(sum(amount - coupon_discount + freight), 0) as amount')
            ->groupBy('user_id')
            ->havingRaw('count(*) >= 3')
            ->orderByDesc('orders')
            ->limit($limit)
            ->get();

        $users = User::query()
            ->whereIn('id', $duplicates->pluck('user_id')->filter()->all())
            ->with('profile:user_id,nickname')
            ->get(['id', 'username'])
            ->keyBy('id');

        return (string) json_encode([
            'generated_at' => now()->toDateTimeString(),
            'stalled' => [
                'hours' => $stalledHours,
                'count' => (clone $stalledQuery)->count(),
                'orders' => (clone $stalledQuery)
                    ->orderBy('updated_at')
                    ->limit($limit)
                    ->get(['no', 'status', 'amount', 'coupon_discount', 'freight', 'user_id', 'updated_at'])
                    ->map(fn (Order $order) => $this->orderRow($order))
                    ->values()
                    ->all(),
            ],
            'refund_pending_shipment' => [
                'count' => (clone $refundingQuery)->count(),
                'orders' => (clone $refundingQuery)
                    ->withCount([
                        'refunds as active_refunds_count' => fn (Builder $query) => $query->whereIn('status', $activeRefundStatuses),
                    ])
                    ->orderBy('paid_at')
                    ->limit($limit)
                    ->get(['no', 'status', 'amount', 'coupon_discount', 'freight', 'user_id', 'updated_at'])
                    ->map(fn (Order $order) => $this->orderRow($order) + [
                        'active_refunds' => (int) $order->active_refunds_count,
                    ])
                    ->values()
                    ->all(),
            ],
            'duplicate_orders' => [
                'hours' => $duplicateHours,
                'users' => $duplicates->map(fn ($row) => [
                    'user_id' => $row->user_id,
                    'username' => $users->get($row->user_id)?->username,
                    'nickname' => $users->get($row->user_id)?->profile?->nickname,
                    'orders' => (int) $row->orders,
                    'amount' => round((float) $row->amount, 2),
                ])->values()->all(),
            ],
            'zero_amount' => [
                'count' => (clone $zeroAmountQuery)->count(),
                'orders' => (clone $zeroAmountQuery)
                    ->latest('id')
                    ->limit($limit)
                    ->get(['no', 'status', 'amount', 'coupon_discount', 'freight', 'user_id', 'updated_at'])
                    ->map(fn (Order $order) => $this->orderRow($order))
                    ->values()
                    ->all(),
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'stalled_hours' => $schema->integer()->description('停滞多久算异常（小时），默认 72'),
            'duplicate_hours' => $schema->integer()->description('重复下单的统计窗口（小时），默认 24'),
            'limit' => $schema->integer()->description('每类异常返回条数，默认 10，最大 50'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function orderRow(Order $order): array
    {
        return [
            'order_no' => $order->no,
            'status' => $order->status->getLabel(),
            'amount' => round((float) $order->amount - (float) $order->coupon_discount + (float) $order->freight, 2),
            'user_id' => $order->user_id,
            'updated_at' => $order->updated_at?->toDateTimeString(),
            'hours_in_status' => $order->updated_at
                ? round(abs($order->updated_at->diffInMinutes(now())) / 60, 1)
                : null,
        ];
    }
}
