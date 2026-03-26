<?php

namespace App\Services;

use App\Contracts\ServiceInterface;
use App\Enums\ApplyStatus;
use App\Models\ReturnAddress;
use App\Models\StoreApply;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Throwable;

class StoreService implements ServiceInterface
{
    /**
     * 设置默认退货地址
     *
     * @param  ReturnAddress  $address
     * @return void
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
     * @param  StoreApply  $apply
     * @param  ApplyStatus|string  $status
     * @param  string|null  $reason
     * @param  Authenticatable|null  $approver
     * @return void
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
