<?php

namespace App\Models;

use App\Enums\RealnameStatus;
use App\Enums\RealnameType;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
class UserRealname extends Model
{
    use BelongsToTenant,
        BelongsToUser,
        SoftDeletes;

    protected $casts = [
        'type' => RealnameType::class,
        'status' => RealnameStatus::class,
        'verified_at' => 'datetime',
    ];

    public function isPending(): bool
    {
        return $this->status === RealnameStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === RealnameStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->status === RealnameStatus::Rejected;
    }
}
