<?php

namespace App\Models\User;

use App\Contracts\Orderable;
use App\Contracts\Refundable;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use App\Models\Mall\RefundItem;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasSortable;
use App\Policies\User\IdentityPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(IdentityPolicy::class)]
class Identity extends Model implements Orderable, Refundable
{
    use BelongsToTenant,
        HasCovers,
        HasEasyStatus,
        HasSortable,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_default' => 'bool',
            'is_unique' => 'bool',
            'can_subscribe' => 'bool',
            'serial_open' => 'bool',
            'conditions' => 'json',
            'rules' => 'json',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        self::saved(static function (self $model) {
            if ($model->is_default) {
                Identity::where('tenant_id', $model->tenant_id)
                    ->where('id', '<>', $model->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * 当前身份对应的用户
     *
     * @return BelongsToMany<User>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_identity')
            ->withPivot(['start_at', 'end_at', 'serial'])
            ->using(UserIdentity::class)
            ->withTimestamps();
    }

    /**
     * 关联订单（通过 OrderItem 多态反查商城订单）
     *
     * @return HasManyThrough<Order>
     */
    public function orders(): HasManyThrough
    {
        return $this->hasManyThrough(
            Order::class,
            OrderItem::class,
            'orderable_id',
            'id',
            'id',
            'order_id',
        )->where('order_items.orderable_type', static::class);
    }

    /**
     * 身份变更日志（作为变更前身份）
     *
     * @return HasMany<IdentityLog>
     */
    public function beforeLogs(): HasMany
    {
        return $this->hasMany(IdentityLog::class, 'before');
    }

    /**
     * 身份变更日志（作为变更后身份）
     *
     * @return HasMany<IdentityLog>
     */
    public function afterLogs(): HasMany
    {
        return $this->hasMany(IdentityLog::class, 'after');
    }

    /**
     * {@inheritDoc}
     */
    public function getTenantId(): int
    {
        return (int) $this->tenant_id;
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderableName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderablePrice(): string
    {
        return number_format((float) $this->price, 2, '.', '');
    }

    /**
     * {@inheritDoc}
     */
    public function checkOrderable(int $qty = 1): ?string
    {
        if (!$this->can_subscribe) {
            return sprintf('身份[%s]不可订阅', $this->name);
        }

        if (!$this->status) {
            return sprintf('身份[%s]已下架', $this->name);
        }

        return null;
    }

    /**
     * {@inheritDoc}
     *
     * 身份为虚拟权益，无库存概念，扣减/回退均为 no-op。
     */
    public function deductStock(int $qty): void
    {
        // no-op
    }

    /**
     * {@inheritDoc}
     */
    public function restoreStock(int $qty): void
    {
        // no-op
    }

    /**
     * {@inheritDoc}
     *
     * 身份为虚拟权益，无需买家寄回。
     */
    public function needsReturn(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     *
     * 退款时撤销已授予的身份：剥离用户关联（不删除身份定义）。
     */
    public function refund(RefundItem $refundItem, int $qty): void
    {
        $user = $refundItem->orderItem?->order?->user;
        if (!$user) {
            return;
        }

        $this->users()->detach($user->getKey());
    }
}
