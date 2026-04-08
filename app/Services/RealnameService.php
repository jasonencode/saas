<?php

namespace App\Services;

use App\Contracts\ServiceInterface;
use App\Enums\User\RealnameStatus;
use App\Events\User\UserRealnameApproved;
use App\Events\User\UserRealnameRejected;
use App\Models\User\UserRealname;

class RealnameService implements ServiceInterface
{
    public function approve(UserRealname $realname): void
    {
        $realname->update([
            'status' => RealnameStatus::Approved,
            'verified_at' => now(),
        ]);

        UserRealnameApproved::dispatch($realname);
    }

    public function reject(UserRealname $realname, string $reason): void
    {
        $realname->update([
            'status' => RealnameStatus::Rejected,
            'reject_reason' => $reason,
        ]);

        UserRealnameRejected::dispatch($realname, $reason);
    }
}
