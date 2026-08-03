<?php

namespace App\Models\Campaign;

use App\Enums\Campaign\RedpackCodeStatus;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\Searchable;
use App\Policies\Campaign\RedpackPolicy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(RedpackPolicy::class)]
class Redpack extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        Searchable,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    /**
     * 关联已领取的核销码
     *
     * @return HasMany<RedpackCode>
     */
    public function claimedCodes(): HasMany
    {
        return $this->codes()->where('status', RedpackCodeStatus::Claimed);
    }

    /**
     * 关联核销码
     *
     * @return HasMany<RedpackCode>
     */
    public function codes(): HasMany
    {
        return $this->hasMany(RedpackCode::class);
    }

    /**
     * 活动是否进行中（已启用 + 时间范围内）
     *
     * @return bool 是否进行中
     */
    public function isActive(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $now = now();

        if ($this->start_at && $now->isBefore($this->start_at)) {
            return false;
        }

        if ($this->end_at && $now->isAfter($this->end_at)) {
            return false;
        }

        return true;
    }

    /**
     * 活动是否已过期
     *
     * @return bool 是否已过期
     */
    public function isExpired(): bool
    {
        return $this->end_at && now()->isAfter($this->end_at);
    }

    /**
     * 进行中的活动作用域
     */
    #[Scope]
    protected function ofActive(Builder $query): void
    {
        $query->ofEnabled()
            ->where(function (Builder $builder) {
                $builder->whereNull('start_at')
                    ->orWhere('start_at', '<=', now());
            })
            ->where(function (Builder $builder) {
                $builder->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            });
    }
}
