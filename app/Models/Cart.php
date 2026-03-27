<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\HasEasyStatus;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 购物车模型
 */
#[Unguarded]
class Cart extends Model
{
    use BelongsToTenant,
        BelongsToUser,
        HasEasyStatus,
        SoftDeletes;

    /**
     * 购物车商品项
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * 获取购物车商品总数
     */
    public function getTotalQtyAttribute(): int
    {
        return $this->items->sum('qty');
    }

    /**
     * 获取购物车总金额
     */
    public function getTotalAmountAttribute(): float
    {
        return (float) $this->items->sum(function ($item) {
            return $item->qty * $item->price_at_add;
        });
    }

    /**
     * 检查购物车是否为空
     */
    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    /**
     * 清空购物车
     */
    public function clear(): void
    {
        $this->items()->delete();
    }

    /**
     * 检查购物车是否过期
     */
    public function isExpired(): bool
    {
        return $this->expired_at && $this->expired_at->isPast();
    }
}
