<?php

namespace App\Models;

use App\Enums\DeliveryType;
use App\Policies\DeliveryPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 运费模板模型
 */
#[Unguarded]
#[UsePolicy(DeliveryPolicy::class)]
class Delivery extends Model
{
    protected $casts = [
        'type' => DeliveryType::class,
        'first' => 'decimal:2',
        'first_fee' => 'decimal:2',
        'additional' => 'decimal:2',
        'additional_fee' => 'decimal:2',
    ];

    /**
     * 关联规则
     *
     * @return HasMany
     */
    public function rules(): HasMany
    {
        return $this->hasMany(DeliveryRule::class);
    }

    /**
     * 关联商品
     *
     * @return HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
