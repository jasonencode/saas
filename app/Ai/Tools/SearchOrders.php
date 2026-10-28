<?php

namespace App\Ai\Tools;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchOrders implements Tool
{
    /**
     * 单次返回的最大订单数，防止刷爆上下文
     */
    protected int $limit = 20;

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return '按条件查询商城订单。支持按订单号、用户（ID/用户名/昵称）、订单状态、金额范围查询，可组合。返回订单号、状态、金额、商品明细等。';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $query = Order::query()->with(['items', 'user.profile:user_id,nickname']);

        // Stringable 对象恒为真，必须转成字符串再判断，否则空参数会穿透进 SQL
        if ($no = (string) $request->str('no')->trim()) {
            $query->where('no', $no);
        }

        if ($status = (string) $request->str('status')->trim()) {
            $query->where('status', OrderStatus::tryFrom($status) ?? $status);
        }

        if ($keyword = (string) $request->str('user')->trim()) {
            // users 表只有 username，昵称在 user_profiles 表
            $query->whereHas('user', function ($q) use ($keyword) {
                $q->where('users.id', $keyword)
                    ->orWhere('users.username', 'like', "%{$keyword}%")
                    ->orWhereHas('profile', fn($p) => $p->where('nickname', 'like', "%{$keyword}%"));
            });
        }

        // 简单的金额范围过滤
        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', $request->float('min_amount'));
        }

        if ($request->filled('max_amount')) {
            $query->where('amount', '<=', $request->float('max_amount'));
        }

        $query->orderByDesc('id')->limit($this->limit);

        // 记录 AI 实际执行的 SQL，便于调试和审计
        $this->logQuery($query);

        $orders = $query->get();

        if ($orders->isEmpty()) {
            return '未找到符合条件的订单。';
        }

        return $orders->map(fn(Order $order) => [
            'order_no' => $order->no,
            'status' => $order->status->getLabel(),
            'total_amount' => $order->total_amount,
            'user' => [
                'id' => $order->user?->id,
                'username' => $order->user?->username,
                'nickname' => $order->user?->profile?->nickname,
            ],
            'items' => $order->items->map(fn($item) => [
                'name' => $item->name,
                'qty' => $item->qty,
                'price' => $item->price,
            ]),
            'created_at' => $order->created_at?->toDateTimeString(),
        ])->toJson(JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'no' => $schema->string()->description('订单号，精确匹配'),
            'status' => $schema->string()->enum(collect(OrderStatus::cases())->map(fn($case) => $case->value)->all())
                ->description('订单状态值（英文小写），如 pending/paid/delivered/completed'),
            'user' => $schema->string()->description('用户 ID、用户名或昵称关键词'),
            'min_amount' => $schema->number()->description('订单金额下限'),
            'max_amount' => $schema->number()->description('订单金额上限'),
        ];
    }

    /**
     * 记录工具执行信息
     */
    protected function logQuery($query): void
    {
        logger()->info('AI tool SearchOrders executed', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);
    }
}
