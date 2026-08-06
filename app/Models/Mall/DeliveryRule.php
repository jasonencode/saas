<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Models\Traits\HasRegion;
use App\Models\Traits\HasSortable;
use App\Policies\Mall\DeliveryRulePolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
#[UsePolicy(DeliveryRulePolicy::class)]
class DeliveryRule extends Model
{
    use HasRegion,
        HasSortable;

    protected function casts(): array
    {
        return [
            'first' => 'decimal:2',
            'first_fee' => 'decimal:2',
            'additional' => 'decimal:2',
            'additional_fee' => 'decimal:2',
            'free_shipping_threshold' => 'decimal:2',
        ];
    }

    /**
     * 包邮门槛
     *
     * null 表示沿用模板，数值 0 表示不包邮。
     */
    public function getFreeShippingThresholdAttribute(): ?string
    {
        $value = $this->attributes['free_shipping_threshold'] ?? null;

        return $value === null ? null : (string) number_format((float) $value, 2, '.', '');
    }

    /**
     * 关联运费模板
     *
     * @return BelongsTo<Delivery>
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }
}
