<?php

namespace App\Models\BlockChain;

use App\Enums\BlockChain\ChainType;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Policies\NetworkPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 区块链网络模型
 */
#[Unguarded]
#[UsePolicy(NetworkPolicy::class)]
class Network extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        SoftDeletes;

    protected $casts = [
        'type' => ChainType::class,
    ];
}
