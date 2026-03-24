<?php

namespace App\Models;

use App\Contracts\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\Unguarded;

/**
 * 系统用户模型
 */
#[Unguarded]
class System extends Authenticatable
{
    /**
     * 获取名称
     *
     * @return string
     */
    protected function getNameAttribute(): string
    {
        return $this->username;
    }
}
