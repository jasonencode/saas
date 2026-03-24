<?php

namespace App\Models;

use App\Contracts\Authenticatable;

/**
 * 系统用户模型
 */
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
