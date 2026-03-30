<?php

namespace App\Models;

use App\Models\Traits\HasEasyStatus;
use App\Policies\AliyunPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 阿里云配置模型
 */
#[Unguarded]
#[UsePolicy(AliyunPolicy::class)]
class Aliyun extends Model
{
    use HasEasyStatus,
        SoftDeletes;

    /**
     * 关联域名
     */
    public function domains(): HasMany
    {
        return $this->hasMany(AliyunDomain::class);
    }

    /**
     * 关联DNS
     */
    public function dns(): HasMany
    {
        return $this->hasMany(AliyunDns::class);
    }

    /**
     * 关联ECS
     */
    public function ecs(): HasMany
    {
        return $this->hasMany(AliyunEcs::class);
    }
}
