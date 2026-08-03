<?php

namespace App\Services\User;

use App\Contracts\ServiceInterface;
use App\Enums\User\RealnameStatus;
use App\Events\User\UserRealnameApproved;
use App\Events\User\UserRealnameRejected;
use App\Models\User\UserRealname;

class RealnameService implements ServiceInterface
{
    /**
     * 审批通过实名认证
     *
     * @param  UserRealname  $realname  实名认证记录
     */
    public function approve(UserRealname $realname): void
    {
        $realname->update([
            'status' => RealnameStatus::Approved,
            'verified_at' => now(),
        ]);

        UserRealnameApproved::dispatch($realname);
    }

    /**
     * 拒绝实名认证
     *
     * @param  UserRealname  $realname  实名认证记录
     * @param  string  $reason  拒绝原因
     */
    public function reject(UserRealname $realname, string $reason): void
    {
        $realname->update([
            'status' => RealnameStatus::Rejected,
            'reject_reason' => $reason,
        ]);

        UserRealnameRejected::dispatch($realname, $reason);
    }
}
