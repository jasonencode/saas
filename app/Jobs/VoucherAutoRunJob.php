<?php

namespace App\Jobs;

use App\Models\Voucher;
use App\Services\SettlementService;

class VoucherAutoRunJob extends BaseJob
{
    public function __construct(protected Voucher $voucher)
    {
    }

    public function handle(): void
    {
        resolve(SettlementService::class)->execute($this->voucher);
    }
}
