<?php

namespace App\Jobs\Mall;

use App\Jobs\BaseJob;
use App\Models\Mall\Refund;
use App\Services\Mall\RefundService;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;

/**
 * 极速退款任务
 *
 * 用户提交未发货订单的仅退款后，异步完成自动审核、自动通过财务审核与原路退回。
 */
#[Timeout(60)]
#[Tries(3)]
class ProcessExpressRefundJob extends BaseJob
{
    public function __construct(
        private readonly Refund $refund,
    ) {}

    public function handle(): void
    {
        service(RefundService::class)->expressRefund($this->refund);
    }
}
