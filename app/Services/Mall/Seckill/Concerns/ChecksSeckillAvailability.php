<?php

namespace App\Services\Mall\Seckill\Concerns;

use App\Enums\Campaign\SeckillReserveFailure;
use App\Enums\Campaign\SeckillSessionStatus;
use App\Models\Campaign\SeckillSession;
use Carbon\CarbonInterface;

/**
 * 场次可购买性校验
 */
trait ChecksSeckillAvailability
{
    /**
     * 返回失败原因，可购买时返回 null
     */
    protected function availabilityFailure(SeckillSession $session, ?CarbonInterface $now = null): ?SeckillReserveFailure
    {
        $now ??= now();

        if ($session->status === SeckillSessionStatus::Cancelled) {
            return SeckillReserveFailure::Cancelled;
        }

        if ($session->status === SeckillSessionStatus::Ended || $now->gt($session->end_at)) {
            return SeckillReserveFailure::Ended;
        }

        if ($now->lt($session->start_at)) {
            return SeckillReserveFailure::NotStarted;
        }

        if ($session->status !== SeckillSessionStatus::Running) {
            return SeckillReserveFailure::NotReady;
        }

        if ($session->remaining() <= 0) {
            return SeckillReserveFailure::SoldOut;
        }

        return null;
    }
}
