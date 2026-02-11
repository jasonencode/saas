<?php

namespace App\Models;

use App\Contracts\Authenticatable;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;

/**
 * 系统用户模型
 */
class System extends Authenticatable
{
    use Cachable;

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
