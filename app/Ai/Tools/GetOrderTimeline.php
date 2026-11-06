<?php

namespace App\Ai\Tools;

use App\Ai\Tools\Concerns\InteractsWithOrders;
use App\Models\System\EventLog;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetOrderTimeline implements Tool
{
    use InteractsWithOrders;

    /**
     * @param  int|null  $tenantId  租户 ID；为空时查询全平台（仅运营端使用）
     */
    public function __construct(protected ?int $tenantId = null) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return '按订单号查询订单事件时间线：状态流转、操作人（用户/管理员/系统）、操作说明与关键时间点（下单、支付、发货、签收、核销、退款）。用户问「这单为什么卡住 / 谁处理的 / 什么时候发货的」时使用。';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $no = trim((string) $request->str('no'));

        if ($no === '') {
            return '请提供订单号（no）。';
        }

        $limit = max(1, min(100, (int) ($request->integer('limit') ?: 30)));

        $order = $this->orderQuery()
            ->with(['shippings', 'refunds'])
            ->where('no', $no)
            ->first();

        if ($order === null) {
            return "未找到订单：{$no}";
        }

        $events = $order->eventLogs()
            ->with('actor')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (EventLog $log) => [
                'at' => $log->created_at?->toDateTimeString(),
                'event' => $log->event,
                'action' => $log->action,
                'description' => $log->description,
                'actor' => $log->actorName(),
                'actor_type' => $log->actorTypeName(),
            ]);

        $deliveryAt = $order->shippings
            ->whereNotNull('delivery_at')
            ->sortBy('delivery_at')
            ->first()
            ?->delivery_at;

        $refundAt = $order->refunds
            ->whereNotNull('refund_at')
            ->sortBy('refund_at')
            ->last()
            ?->refund_at;

        return (string) json_encode([
            'order_no' => $order->no,
            'status' => $order->status->getLabel(),
            'events_total' => $order->eventLogs()->count(),
            'key_times' => array_filter([
                'created_at' => $order->created_at?->toDateTimeString(),
                'paid_at' => $order->paid_at?->toDateTimeString(),
                'delivered_at' => $deliveryAt?->toDateTimeString(),
                'signed_at' => $order->signed_at?->toDateTimeString(),
                'verified_at' => $order->verified_at?->toDateTimeString(),
                'refund_at' => $refundAt?->toDateTimeString(),
            ], static fn ($value) => $value !== null),
            'events' => $events->all(),
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'no' => $schema->string()->description('订单号，精确匹配'),
            'limit' => $schema->integer()->description('返回的最近事件条数，默认 30，最大 100'),
        ];
    }
}
