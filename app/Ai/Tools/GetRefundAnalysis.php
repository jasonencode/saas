<?php

namespace App\Ai\Tools;

use App\Ai\Tools\Concerns\ResolvesCalendarPeriod;
use App\Ai\Tools\Concerns\UsesOrderStatuses;
use App\Enums\Mall\RefundReason;
use App\Enums\Mall\RefundStatus;
use App\Models\Mall\Order;
use App\Models\Mall\Refund;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetRefundAnalysis implements Tool
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
        return '退款分析：统计今日 / 本周 / 本月 / 今年或自定义周期内的退款单数、退款金额、退款率、处理时效，并按退款原因、退款状态、退款商品排行给出结构。用户询问「退款情况 / 退款原因 / 退款率高不高 / 哪些商品退款多」时使用。';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $limit = max(1, min(50, $request->integer('limit') ?: 10));

        [$start, $end, $label] = $this->resolveCalendarPeriod(
            (string) $request->str('period'),
            (string) $request->str('start_date'),
            (string) $request->str('end_date'),
        );

        $refundQuery = $this->refundQuery($start, $end);

        $count = (clone $refundQuery)->count();
        $amount = (float) (clone $refundQuery)->sum('total');

        $paidAmount = (float) Order::query()
            ->when($this->tenantId !== null, fn (Builder $query) => $query->where('tenant_id', $this->tenantId))
            ->whereIn('status', $this->salesStatuses())
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw('coalesce(sum(amount - coupon_discount + freight), 0) as total')
            ->value('total');

        $completed = (clone $refundQuery)
            ->where('status', RefundStatus::Completed)
            ->whereNotNull('refund_at')
            ->get(['created_at', 'refund_at']);

        $avgCompleteHours = $completed->isEmpty()
            ? 0
            : round((float) $completed->avg(
                fn (Refund $refund) => $refund->created_at?->diffInMinutes($refund->refund_at) / 60
            ), 2);

        $statusCounts = (clone $refundQuery)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $reasons = (clone $refundQuery)
            ->selectRaw('reason, count(*) as total, coalesce(sum(total), 0) as amount')
            ->groupBy('reason')
            ->orderByDesc('amount')
            ->get();

        $topProducts = (clone $refundQuery)
            ->join('refund_items', 'refund_items.refund_id', '=', 'refunds.id')
            ->join('order_items', 'order_items.id', '=', 'refund_items.order_item_id')
            ->selectRaw(
                'order_items.orderable_type as group_type, '.
                'order_items.orderable_id as group_id, '.
                'max(order_items.orderable_name) as name, '.
                'sum(refund_items.qty) as qty, '.
                'sum(refund_items.qty * refund_items.price) as amount, '.
                'count(distinct refunds.id) as refunds'
            )
            ->groupBy('order_items.orderable_type', 'order_items.orderable_id')
            ->orderByDesc('amount')
            ->limit($limit)
            ->get();

        return (string) json_encode([
            'period' => [
                'label' => $label,
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'totals' => [
                'count' => $count,
                'amount' => round($amount, 2),
                'avg_amount' => $count > 0 ? round($amount / $count, 2) : 0,
                'paid_amount' => round($paidAmount, 2),
                'refund_rate' => $paidAmount > 0 ? round($amount / $paidAmount * 100, 2) : 0,
                'completed_count' => $completed->count(),
                'avg_complete_hours' => $avgCompleteHours,
            ],
            'by_status' => $this->statusSummary($statusCounts),
            'by_reason' => $reasons->map(fn ($row) => [
                'reason' => $this->reasonLabel($row->reason),
                'count' => (int) $row->total,
                'amount' => round((float) $row->amount, 2),
                'share' => $amount > 0 ? round((float) $row->amount / $amount * 100, 2) : 0,
            ])->values()->all(),
            'top_products' => $topProducts->map(fn ($row) => [
                'name' => $row->name ?: '商品#'.$row->group_id,
                'qty' => (int) $row->qty,
                'amount' => round((float) $row->amount, 2),
                'refunds' => (int) $row->refunds,
                'share' => $amount > 0 ? round((float) $row->amount / $amount * 100, 2) : 0,
            ])->values()->all(),
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'period' => $schema->string()
                ->enum(['today', 'week', 'month', 'year', 'custom'])
                ->description('统计周期：today=今天，week=本周，month=本月，year=今年，custom=自定义（需传 start_date/end_date），默认 month'),
            'start_date' => $schema->string()->description('自定义开始日期，格式 Y-m-d'),
            'end_date' => $schema->string()->description('自定义结束日期，格式 Y-m-d'),
            'limit' => $schema->integer()->description('退款商品排行条数，默认 10，最大 50'),
        ];
    }

    /**
     * 退款查询：限定周期、租户与有效退款状态
     */
    private function refundQuery(Carbon $start, Carbon $end): Builder
    {
        return Refund::query()
            ->when($this->tenantId !== null, fn (Builder $query) => $query->where('tenant_id', $this->tenantId))
            ->whereIn('status', array_map(fn (RefundStatus $status) => $status->value, RefundStatus::effectiveCases()))
            ->whereBetween('created_at', [$start, $end]);
    }

    /**
     * @param  Collection<string, int>  $counts
     *
     * @return array<string, int>
     */
    private function statusSummary(Collection $counts): array
    {
        return collect(RefundStatus::cases())
            ->mapWithKeys(fn (RefundStatus $status) => [
                $status->getLabel() => (int) ($counts[$status->value] ?? 0),
            ])
            ->all();
    }

    private function reasonLabel(mixed $reason): string
    {
        return match (true) {
            $reason instanceof RefundReason => $reason->getLabel(),
            is_string($reason) => RefundReason::tryFrom($reason)?->getLabel() ?? $reason,
            default => '未填写',
        };
    }
}
