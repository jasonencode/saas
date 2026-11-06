<?php

namespace App\Ai\Tools;

use App\Ai\Tools\Concerns\ResolvesCalendarPeriod;
use App\Ai\Tools\Concerns\UsesOrderStatuses;
use App\Enums\Mall\RefundStatus;
use App\Models\Mall\OrderItem;
use App\Models\Mall\Product;
use App\Models\Mall\ProductCategory;
use App\Models\Mall\RefundItem;
use App\Models\Mall\Sku;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetProductSalesReport implements Tool
{
    use ResolvesCalendarPeriod;
    use UsesOrderStatuses;

    /**
     * @param  int|null  $tenantId  租户 ID；为空时统计全平台（仅运营端使用）
     */
    public function __construct(protected ?int $tenantId = null) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return '商品销售报告：按「单品」或「商品分类」统计今日 / 本周 / 本月 / 今年或自定义日期范围的销量、销售额、订单数、退款与净销售额，并按销售额返回 TOP N。用户询问「哪些商品卖得好 / 分类销售排行 / 某商品销量 / 周报月报年报」时使用。';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $isCategory = (string) $request->str('dimension') === 'category';
        $keyword = trim((string) $request->str('keyword'));
        $limit = max(1, min(50, $request->integer('limit') ?: 10));

        [$start, $end, $label] = $this->resolveCalendarPeriod(
            (string) $request->str('period'),
            (string) $request->str('start_date'),
            (string) $request->str('end_date'),
        );

        $salesQuery = $this->salesQuery($start, $end, $isCategory, $keyword);

        $totals = (clone $salesQuery)->selectRaw(
            'coalesce(sum(order_items.qty), 0) as qty, '.
            'coalesce(sum(order_items.qty * order_items.price - order_items.coupon_discount), 0) as amount, '.
            'count(distinct orders.id) as orders'
        )->first();

        $rows = (clone $salesQuery)
            ->select(array_merge(
                $this->groupSelects($isCategory),
                [
                    DB::raw('coalesce(p.category_id, sp.category_id) as category_id'),
                    DB::raw('max(order_items.orderable_name) as name'),
                    DB::raw('sum(order_items.qty) as qty'),
                    DB::raw('sum(order_items.qty * order_items.price - order_items.coupon_discount) as amount'),
                    DB::raw('count(distinct orders.id) as orders'),
                ],
            ))
            ->groupBy($this->groupBys($isCategory))
            ->orderByDesc('amount')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            return sprintf('所选周期（%s）内没有销售数据。', $label);
        }

        $refundQuery = $this->refundQuery($start, $end, $isCategory, $keyword);

        $refundTotals = (clone $refundQuery)
            ->selectRaw(
                'coalesce(sum(refund_items.qty), 0) as qty, '.
                'coalesce(sum(refund_items.qty * refund_items.price), 0) as amount'
            )
            ->first();

        $refunds = (clone $refundQuery)
            ->select(array_merge(
                $this->groupSelects($isCategory),
                [
                    DB::raw('sum(refund_items.qty) as qty'),
                    DB::raw('sum(refund_items.qty * refund_items.price) as amount'),
                ],
            ))
            ->groupBy($this->groupBys($isCategory))
            ->get()
            ->keyBy(fn (object $row): string => $this->rowKey($row, $isCategory));

        $categoryNames = ProductCategory::query()
            ->whereIn('id', $rows->pluck('category_id')->filter()->unique()->all())
            ->pluck('name', 'id');

        $totalAmount = (float) ($totals->amount ?? 0);

        $items = $rows->map(function (object $row) use ($isCategory, $categoryNames, $refunds, $totalAmount): array {
            $refund = $refunds->get($this->rowKey($row, $isCategory));

            $amount = (float) $row->amount;
            $refundAmount = (float) ($refund->amount ?? 0);
            $categoryName = $categoryNames[$row->category_id] ?? '未分类';

            return [
                'name' => $isCategory
                    ? $categoryName
                    : ($row->name ?: '商品#'.$row->group_id),
                'category' => $isCategory ? null : $categoryName,
                'qty' => (int) $row->qty,
                'amount' => round($amount, 2),
                'orders' => (int) $row->orders,
                'refund_qty' => (int) ($refund->qty ?? 0),
                'refund_amount' => round($refundAmount, 2),
                'net_amount' => round($amount - $refundAmount, 2),
                'share' => $totalAmount > 0 ? round($amount / $totalAmount * 100, 2) : 0,
            ];
        })->values()->all();

        return (string) json_encode([
            'period' => [
                'label' => $label,
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'dimension' => $isCategory ? '分类' : '单品',
            'totals' => [
                'qty' => (int) ($totals->qty ?? 0),
                'amount' => round($totalAmount, 2),
                'orders' => (int) ($totals->orders ?? 0),
                'refund_qty' => (int) ($refundTotals->qty ?? 0),
                'refund_amount' => round((float) ($refundTotals->amount ?? 0), 2),
                'net_amount' => round($totalAmount - (float) ($refundTotals->amount ?? 0), 2),
            ],
            'items' => $items,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'dimension' => $schema->string()
                ->enum(['product', 'category'])
                ->description('统计维度：product=单品，category=商品分类，默认 product'),
            'period' => $schema->string()
                ->enum(['today', 'week', 'month', 'year', 'custom'])
                ->description('统计周期：today=今天，week=本周，month=本月，year=今年，custom=自定义（需传 start_date/end_date），默认 month'),
            'start_date' => $schema->string()->description('自定义开始日期，格式 Y-m-d，period=custom 时使用'),
            'end_date' => $schema->string()->description('自定义结束日期，格式 Y-m-d，period=custom 时使用'),
            'keyword' => $schema->string()->description('按商品名称关键词过滤，仅 product 维度生效'),
            'limit' => $schema->integer()->description('返回条数，默认 10，最大 50'),
        ];
    }

    /**
     * 销售明细查询：只统计已支付及之后的订单，按支付时间归属周期
     */
    private function salesQuery(Carbon $start, Carbon $end, bool $isCategory, string $keyword): Builder
    {
        $query = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNull('orders.deleted_at')
            ->whereIn('orders.status', $this->salesStatuses())
            ->whereBetween('orders.paid_at', [$start, $end])
            ->when($this->tenantId !== null, fn (Builder $query) => $query->where('orders.tenant_id', $this->tenantId));

        $this->joinOrderables($query);

        if (!$isCategory && $keyword !== '') {
            $query->where('order_items.orderable_name', 'like', "%$keyword%");
        }

        return $query;
    }

    /**
     * 退款查询：按退款申请时间统计进行中与已完成的退款
     */
    private function refundQuery(Carbon $start, Carbon $end, bool $isCategory, string $keyword): Builder
    {
        $query = RefundItem::query()
            ->join('refunds', 'refunds.id', '=', 'refund_items.refund_id')
            ->join('order_items', 'order_items.id', '=', 'refund_items.order_item_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNull('refunds.deleted_at')
            ->whereNull('orders.deleted_at')
            ->whereIn('refunds.status', array_map(fn (RefundStatus $status) => $status->value, RefundStatus::effectiveCases()))
            ->whereBetween('refunds.created_at', [$start, $end])
            ->when($this->tenantId !== null, fn (Builder $query) => $query->where('refunds.tenant_id', $this->tenantId));

        $this->joinOrderables($query);

        if (!$isCategory && $keyword !== '') {
            $query->where('order_items.orderable_name', 'like', "%$keyword%");
        }

        return $query;
    }

    /**
     * 关联商品与 SKU，便于按商品分类聚合
     */
    private function joinOrderables(Builder $query): void
    {
        $query->leftJoin('products as p', function ($join): void {
            $join->on('p.id', '=', 'order_items.orderable_id')
                ->where('order_items.orderable_type', Product::class);
        })->leftJoin('skus as s', function ($join): void {
            $join->on('s.id', '=', 'order_items.orderable_id')
                ->where('order_items.orderable_type', Sku::class);
        })->leftJoin('products as sp', 'sp.id', '=', 's.product_id');
    }

    /**
     * @return array<int, mixed>
     */
    private function groupSelects(bool $isCategory): array
    {
        if ($isCategory) {
            return [DB::raw('coalesce(p.category_id, sp.category_id) as group_id')];
        }

        return [
            DB::raw('order_items.orderable_type as group_type'),
            DB::raw('order_items.orderable_id as group_id'),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function groupBys(bool $isCategory): array
    {
        if ($isCategory) {
            return [DB::raw('coalesce(p.category_id, sp.category_id)')];
        }

        return [
            'order_items.orderable_type',
            'order_items.orderable_id',
            DB::raw('coalesce(p.category_id, sp.category_id)'),
        ];
    }

    private function rowKey(object $row, bool $isCategory): string
    {
        return $isCategory
            ? (string) ($row->group_id ?? 0)
            : $row->group_type.'|'.$row->group_id;
    }
}
