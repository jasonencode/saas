<?php

namespace App\Models;

use App\Enums\ChainType;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 区块链网络模型
 */
#[Unguarded]
class Network extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        SoftDeletes;

    protected $casts = [
        'type' => ChainType::class,
    ];
}
