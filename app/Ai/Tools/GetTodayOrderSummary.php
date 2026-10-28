<?php

namespace App\Ai\Tools;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetTodayOrderSummary implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return '获取今日商城订单概况：各状态订单数量、成交总额、最近 10 笔订单列表。用户询问「今天订单怎么样/今日概况/日报」时使用。';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $today = Order::query()->whereDate('created_at', today());

        $statusCounts = (clone $today)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statuses = collect(OrderStatus::cases())
            ->map(fn (OrderStatus $status) => [
                'status' => $status->getLabel(),
                'count' => (int) ($statusCounts[$status->value] ?? 0),
            ]);

        $paidAmount = (clone $today)
            ->where('status', '!=', OrderStatus::Pending)
            ->where('status', '!=', OrderStatus::Canceled)
            ->get(['amount', 'coupon_discount', 'freight'])
            ->sum(fn (Order $order) => $order->total_amount);

        $recentOrders = (clone $today)
            ->with('user.profile:user_id,nickname')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn (Order $order) => [
                'order_no' => $order->no,
                'status' => $order->status->getLabel(),
                'total_amount' => $order->total_amount,
                'user' => $order->user?->profile?->nickname ?? $order->user?->username,
                'created_at' => $order->created_at?->toDateTimeString(),
            ]);

        return json_encode([
            'date' => today()->toDateString(),
            'status_summary' => $statuses,
            'total_orders' => (clone $today)->count(),
            'paid_amount' => round((float) $paidAmount, 2),
            'recent_orders' => $recentOrders,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            //
        ];
    }
}
