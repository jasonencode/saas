<?php

namespace App\Models\Campaign;

use App\Enums\Campaign\SeckillActivityStatus;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEventLog;
use App\Policies\Campaign\SeckillActivityPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 秒杀活动
 *
 * 营销容器，具体售卖规则由场次（SeckillSession）承载。
 *
 * @property SeckillActivityStatus $status
 */
#[Unguarded]
#[UsePolicy(SeckillActivityPolicy::class)]
class SeckillActivity extends Model
{
    use BelongsToTenant,
        HasEventLog,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => SeckillActivityStatus::class,
            'sort' => 'integer',
        ];
    }

    /**
     * 场次列表
     *
     * @return HasMany<SeckillSession>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(SeckillSession::class, 'activity_id');
    }

    /**
     * 是否已发布
     */
    public function isPublished(): bool
    {
        return $this->status === SeckillActivityStatus::Published;
    }
}
