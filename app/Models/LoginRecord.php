<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 登录记录模型
 */
class LoginRecord extends Model
{
    const null UPDATED_AT = null;

    /**
     * 关联用户
     *
     * @return MorphTo
     */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }
}
