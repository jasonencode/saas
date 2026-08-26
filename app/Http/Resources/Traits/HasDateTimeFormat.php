<?php

namespace App\Http\Resources\Traits;

use Carbon\Carbon;

trait HasDateTimeFormat
{
    /**
     * 格式化时间为 ISO 8601 格式（带时区）
     *
     * @param  Carbon|string|null  $datetime  时间
     *
     * @return string|null 格式化后的时间（2024-01-01T12:00:00+08:00）
     */
    protected function formatDateTime(Carbon|string|null $datetime): ?string
    {
        if ($datetime === null) {
            return null;
        }

        if (is_string($datetime)) {
            $datetime = Carbon::parse($datetime);
        }

        return $datetime->toIso8601String();
    }

    /**
     * 格式化日期（仅日期部分）
     *
     * @param  Carbon|string|null  $date  日期
     *
     * @return string|null 格式化后的日期（2024-01-01）
     */
    protected function formatDate(Carbon|string|null $date): ?string
    {
        if ($date === null) {
            return null;
        }

        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        return $date->toDateString();
    }
}
