<?php

namespace App\Ai\Tools\Concerns;

use App\Models\Mall\Order;
use Illuminate\Database\Eloquent\Builder;

/**
 * 订单相关工具的公用的租户查询与脱敏
 */
trait InteractsWithOrders
{
    /**
     * 订单查询，已按当前租户过滤
     */
    protected function orderQuery(): Builder
    {
        return Order::query()
            ->when($this->tenantId !== null, fn (Builder $query) => $query->where('tenant_id', $this->tenantId));
    }

    /**
     * 手机号脱敏
     */
    protected function maskMobile(?string $mobile): ?string
    {
        if ($mobile === null || mb_strlen($mobile) < 7) {
            return $mobile;
        }

        return mb_substr($mobile, 0, 3).'****'.mb_substr($mobile, -4);
    }
}
