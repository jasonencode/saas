<?php

namespace App\Ai\Tools;

use App\Ai\Tools\Concerns\ResolvesCalendarPeriod;
use App\Ai\Tools\Concerns\UsesOrderStatuses;
use App\Enums\Mall\RefundStatus;
use App\Models\Mall\Order;
use App\Models\Mall\Refund;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetSalesTrend implements Tool
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
        return '销售趋势：按天 / 周 / 月统计最近 7 天、最近 30 天、最近 12 个月或自定义周期的支付订单数、支付金额、退款金额与净销售额，并给出与上一周期的对比。用户询问「销售趋势 / 最近几天卖得怎么样 / 月度走势 / 环比」时使用。';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        [$start, $end, $label] = $this->resolveTrendPeriod(
            (string) $request->str('period'),
            (string) $request->str('start_date'),
            (string) $request->str('end_date'),
        );

        $granularity = $this->resolveGranularity(
            (string) $request->str('granularity'),
            (string) $request->str('period'),
            $start,
            $end,
        );

        $dayExpression = $this->dateExpression('paid_at');
        $refundDayExpression = $this->dateExpression('created_at');

        $salesRows = Order::query()
            ->when($this->tenantId !== null, fn (Builder $query) => $query->where('tenant_id', $this->tenantId))
            ->whereIn('status', $this->salesStatuses())
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw(
                $dayExpression.' as bucket, '.
                'count(*) as orders, '.
                'coalesce(sum(amount - coupon_discount + freight), 0) as amount'
            )
            ->groupByRaw($dayExpression)
            ->get()
            ->keyBy('bucket');

        $refundRows = Refund::query()
            ->when($this->tenantId !== null, fn (Builder $query) => $query->where('tenant_id', $this->tenantId))
            ->whereIn('status', array_map(fn (RefundStatus $status) => $status->value, RefundStatus::effectiveCases()))
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw($refundDayExpression.' as bucket, coalesce(sum(total), 0) as amount')
            ->groupByRaw($refundDayExpression)
            ->get()
            ->keyBy('bucket');

        $buckets = $this->buildBuckets($start, $end, $granularity, $salesRows, $refundRows);

        $totals = [
            'orders' => array_sum(array_column($buckets, 'orders')),
            'amount' => round(array_sum(array_column($buckets, 'amount')), 2),
            'refund_amount' => round(array_sum(array_column($buckets, 'refund_amount')), 2),
        ];
        $totals['net_amount'] = round($totals['amount'] - $totals['refund_amount'], 2);

        $previous = $this->previousTotals($start, $end);

        return (string) json_encode([
            'period' => [
                'label' => $label,
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'granularity' => $granularity,
            ],
            'totals' => $totals,
            'comparison' => [
                'orders' => (int) ($previous->orders ?? 0),
                'amount' => round((float) ($previous->amount ?? 0), 2),
                'orders_change' => $this->changeRate((int) ($previous->orders ?? 0), $totals['orders']),
                'amount_change' => $this->changeRate((float) ($previous->amount ?? 0), $totals['amount']),
            ],
            'buckets' => $buckets,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'period' => $schema->string()
                ->enum(['week', 'month', 'year', 'custom'])
                ->description('统计范围：week=最近 7 天，month=最近 30 天，year=最近 12 个月，custom=自定义（需传 start_date/end_date），默认 week'),
            'start_date' => $schema->string()->description('自定义开始日期，格式 Y-m-d'),
            'end_date' => $schema->string()->description('自定义结束日期，格式 Y-m-d'),
            'granularity' => $schema->string()
                ->enum(['day', 'week', 'month'])
                ->description('聚合粒度：day=按天，week=按周，month=按月；默认最近 7/30 天按天、12 个月按月'),
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function resolveTrendPeriod(string $period, string $startDate, string $endDate): array
    {
        $now = now();

        return match ($period) {
            'month' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay(), '最近30天'],
            'year' => [$now->copy()->subMonths(11)->startOfMonth(), $now->copy()->endOfDay(), '最近12个月'],
            'custom' => $this->resolveCalendarPeriod('custom', $startDate, $endDate),
            default => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay(), '最近7天'],
        };
    }

    private function resolveGranularity(string $granularity, string $period, Carbon $start, Carbon $end): string
    {
        if (in_array($granularity, ['day', 'week', 'month'], true)) {
            return $granularity;
        }

        if ($period === 'year') {
            return 'month';
        }

        if ($period === 'custom' && $start->diffInDays($end) > 62) {
            return 'month';
        }

        return 'day';
    }

    /**
     * 按天取数后在 PHP 中归并成天/周/月桶，保证没有数据的区间补 0
     */
    private function buildBuckets(Carbon $start, Carbon $end, string $granularity, Collection $salesRows, Collection $refundRows): array
    {
        $buckets = [];

        $cursor = $start->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($end)) {
            $key = match ($granularity) {
                'month' => $cursor->format('Y-m'),
                'week' => $cursor->copy()->startOfWeek()->toDateString(),
                default => $cursor->toDateString(),
            };

            $buckets[$key] ??= [
                'bucket' => $key,
                'label' => $this->bucketLabel($key, $granularity),
                'orders' => 0,
                'amount' => 0.0,
                'refund_amount' => 0.0,
                'net_amount' => 0.0,
            ];

            $sales = $salesRows->get($cursor->toDateString());
            $refund = $refundRows->get($cursor->toDateString());

            $buckets[$key]['orders'] += (int) ($sales->orders ?? 0);
            $buckets[$key]['amount'] += (float) ($sales->amount ?? 0);
            $buckets[$key]['refund_amount'] += (float) ($refund->amount ?? 0);

            $cursor->addDay();
        }

        return array_values(array_map(static function (array $bucket): array {
            $bucket['amount'] = round($bucket['amount'], 2);
            $bucket['refund_amount'] = round($bucket['refund_amount'], 2);
            $bucket['net_amount'] = round($bucket['amount'] - $bucket['refund_amount'], 2);

            return $bucket;
        }, $buckets));
    }

    private function bucketLabel(string $key, string $granularity): string
    {
        return $granularity === 'week' ? $key.' 起' : $key;
    }

    /**
     * 上一等长周期的支付金额与订单数
     */
    private function previousTotals(Carbon $start, Carbon $end): ?object
    {
        $days = (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;
        $previousEnd = $start->copy()->subSecond();
        $previousStart = $previousEnd->copy()->subDays($days - 1)->startOfDay();

        return Order::query()
            ->when($this->tenantId !== null, fn (Builder $query) => $query->where('tenant_id', $this->tenantId))
            ->whereIn('status', $this->salesStatuses())
            ->whereBetween('paid_at', [$previousStart, $previousEnd])
            ->selectRaw(
                'count(*) as orders, '.
                'coalesce(sum(amount - coupon_discount + freight), 0) as amount'
            )
            ->first();
    }

    private function changeRate(float|int $previous, float|int $current): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round(($current - $previous) / $previous * 100, 2);
    }

    /**
     * 兼容 PostgreSQL / SQLite / MySQL 的按天表达式
     */
    private function dateExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => "to_char({$column}, 'YYYY-MM-DD')",
            'sqlite' => "strftime('%Y-%m-%d', {$column})",
            default => "date_format({$column}, '%Y-%m-%d')",
        };
    }
}
