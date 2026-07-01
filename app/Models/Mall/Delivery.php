<?php

namespace App\Models\Mall;

use App\Enums\Mall\DeliveryType;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Policies\Mall\DeliveryPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(DeliveryPolicy::class)]
class Delivery extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        SoftDeletes;

    protected $casts = [
        'type' => DeliveryType::class,
        'first' => 'decimal:2',
        'first_fee' => 'decimal:2',
        'additional' => 'decimal:2',
        'additional_fee' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'is_default' => 'bool',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::saving(static function (self $delivery) {
            // 仅当 is_default 从 false 变为 true 时才重置其他记录
            if ($delivery->is_default
                && $delivery->tenant_id
                && $delivery->isDirty('is_default')
            ) {
                static::where('tenant_id', $delivery->tenant_id)
                    ->where('id', '!=', $delivery->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * 关联规则
     */
    public function rules(): HasMany
    {
        return $this->hasMany(DeliveryRule::class);
    }

    /**
     * 关联商品
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
