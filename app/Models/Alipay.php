<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Policies\AlipayPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 支付宝配置模型
 */
#[Unguarded]
#[UsePolicy(AlipayPolicy::class)]
class Alipay extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        SoftDeletes;

    public function getConfig(): array
    {
        return [];
    }
}
