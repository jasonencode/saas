<?php

namespace App\Ai\Tools\Concerns;

use Carbon\Carbon;
use Throwable;

/**
 * 解析统计周期（日/周/月/年/自定义）
 */
trait ResolvesCalendarPeriod
{
    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    protected function resolveCalendarPeriod(string $period, string $startDate = '', string $endDate = ''): array
    {
        $now = now();

        return match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay(), '今天'],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfDay(), '本周'],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfDay(), '今年'],
            'custom' => $this->customPeriod($startDate, $endDate),
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfDay(), '本月'],
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function customPeriod(string $startDate, string $endDate): array
    {
        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
        } catch (Throwable) {
            $now = now();

            return [$now->copy()->startOfMonth(), $now->copy()->endOfDay(), '本月'];
        }

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end, '自定义'];
    }
}
