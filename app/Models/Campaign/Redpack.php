<?php

namespace App\Models\Campaign;

use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Policies\RedpackPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 红包模型
 */
#[Unguarded]
#[UsePolicy(RedpackPolicy::class)]
class Redpack extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        SoftDeletes;

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    /**
     * 关联核销码
     *
     * @return HasMany
     */
    public function codes(): HasMany
    {
        return $this->hasMany(RedpackCode::class);
    }
}
