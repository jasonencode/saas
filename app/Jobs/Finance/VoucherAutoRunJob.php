<?php

namespace App\Jobs\Finance;

use App\Jobs\BaseJob;
use App\Models\Finance\Voucher;
use App\Services\Finance\SettlementService;
use Throwable;

/**
 * 凭证自动执行任务类
 */
class VoucherAutoRunJob extends BaseJob
{
    public function __construct(protected Voucher $voucher) {}

    public function handle(): void
    {
        try {
            service(SettlementService::class)
                ->execute($this->voucher);
        } catch (Throwable $e) {
            // 结算失败不自动重试：部分任务可能已产生事务外副作用，
            // 由后台「执行结算」动作人工确认后重跑更安全
            report($e);
        }
    }
}
