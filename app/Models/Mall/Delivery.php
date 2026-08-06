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
use Illuminate\Support\Facades\DB;

#[Unguarded]
#[UsePolicy(DeliveryPolicy::class)]
class Delivery extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => DeliveryType::class,
            'first' => 'decimal:2',
            'first_fee' => 'decimal:2',
            'additional' => 'decimal:2',
            'additional_fee' => 'decimal:2',
            'free_shipping_threshold' => 'decimal:2',
            'is_default' => 'bool',
        ];
    }

    /**
     * 包邮门槛
     *
     * null 表示沿用模板（仅 DeliveryRule 有效），数值 0 表示不包邮。
     * cast 为 decimal:2 时 null 保留为 null，不会被转成字符串。
     */
    public function getFreeShippingThresholdAttribute(): ?string
    {
        $value = $this->attributes['free_shipping_threshold'] ?? null;

        return $value === null ? null : (string) number_format((float) $value, 2, '.', '');
    }

    public static function boot(): void
    {
        parent::boot();

        static::saving(static function (self $delivery) {
            // 仅当 is_default 从 false 变为 true 时才重置其他记录
            if (!$delivery->is_default || !$delivery->tenant_id || !$delivery->isDirty('is_default')) {
                return;
            }

            DB::transaction(static function () use ($delivery) {
                static::where('tenant_id', $delivery->tenant_id)
                    ->where('is_default', true)
                    ->when($delivery->exists, fn ($query) => $query->where('id', '!=', $delivery->getKey()))
                    ->lockForUpdate()
                    ->update(['is_default' => false]);
            });
        });
    }

    /**
     * 关联规则
     *
     * @return HasMany<DeliveryRule>
     */
    public function rules(): HasMany
    {
        return $this->hasMany(DeliveryRule::class);
    }

    /**
     * 关联商品
     *
     * @return HasMany<Product>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
