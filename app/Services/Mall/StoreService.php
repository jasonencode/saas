<?php

namespace App\Services\Mall;

use App\Contracts\ServiceInterface;
use App\Enums\Mall\ApplyStatus;
use App\Models\Mall\ReturnAddress;
use App\Models\Mall\StoreApply;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Throwable;

class StoreService implements ServiceInterface
{
    /**
     * 设置默认退货地址
     *
     * @throws Throwable
     */
    public function setDefaultReturnAddress(ReturnAddress $address): void
    {
        DB::transaction(static function () use ($address) {
            ReturnAddress::where('tenant_id', $address->tenant_id)
                ->where('is_default', true)
                ->update(['is_default' => false]);

            $address->is_default = true;
            $address->save();
        });
    }

    /**
     * 审核店铺申请
     *
     * @param  StoreApply  $apply  申请记录
     * @param  ApplyStatus|string  $status  审核状态
     * @param  string|null  $reason  审核原因/备注
     * @param  Authenticatable|null  $approver  审核人
     */
    public function auditApply(StoreApply $apply, ApplyStatus|string $status, ?string $reason = null, ?Authenticatable $approver = null): void
    {
        $approver = $approver ?: Filament::auth()->user();

        $apply->status = $status;
        if ($status === ApplyStatus::Rejected->value || $status === ApplyStatus::Rejected) {
            $apply->reason = $reason;
        } else {
            $apply->remark = $reason;
        }

        if ($approver) {
            $apply->approver_type = $approver->getMorphClass();
            $apply->approver_id = $approver->getKey();
        }

        $apply->save();
    }
}
