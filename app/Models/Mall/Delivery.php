<?php

namespace App\Models\Mall;

use App\Enums\Mall\DeliveryType;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasSortable;
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
        HasSortable,
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

    public static function boot(): void
    {
        parent::boot();

        static::saved(static function (self $delivery) {
            if ($delivery->is_default && $delivery->tenant_id) {
                static::where('tenant_id', $delivery->tenant_id)
                    ->where('id', '!=', $delivery->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
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
