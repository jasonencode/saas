<?php

namespace App\Models;

use App\Policies\DeliveryRulePolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 运费规则模型
 */
#[Unguarded]
#[UsePolicy(DeliveryRulePolicy::class)]
class DeliveryRule extends Model
{
    /**
     * 关联运费模板
     *
     * @return BelongsTo
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }
}
