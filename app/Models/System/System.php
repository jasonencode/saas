<?php

namespace App\Models\System;

use App\Contracts\Authenticatable;
use App\Policies\System\SystemPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;

/**
 * 系统用户模型
 */
#[Unguarded]
#[UsePolicy(SystemPolicy::class)]
class System extends Authenticatable
{
    /**
     * 获取名称
     *
     * @return string
     */
    protected function getNameAttribute(): string
    {
        return $this->attributes['name'];
    }
}
