<?php

namespace App\Models\User;

use App\Enums\User\RealnameStatus;
use App\Enums\User\RealnameType;
use App\Models\Model;
use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
class UserRealname extends Model
{
    use BelongsToUser,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => RealnameType::class,
            'status' => RealnameStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    /**
     * 是否待审核状态
     *
     * @return bool 是否待审核
     */
    public function isPending(): bool
    {
        return $this->status === RealnameStatus::Pending;
    }

    /**
     * 是否已通过状态
     *
     * @return bool 是否已通过
     */
    public function isApproved(): bool
    {
        return $this->status === RealnameStatus::Approved;
    }

    /**
     * 是否已拒绝状态
     *
     * @return bool 是否已拒绝
     */
    public function isRejected(): bool
    {
        return $this->status === RealnameStatus::Rejected;
    }
}
