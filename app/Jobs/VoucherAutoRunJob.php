<?php

namespace App\Jobs;

use App\Models\Finance\Voucher;
use App\Services\SettlementService;
use Exception;

/**
 * 凭证自动执行任务类
 */
class VoucherAutoRunJob extends BaseJob
{
    public function __construct(protected Voucher $voucher)
    {
    }

    public function handle(): void
    {
        try {
            service(SettlementService::class)->execute($this->voucher);
        } catch (Exception $e) {
            report($e);
        }
    }
}
