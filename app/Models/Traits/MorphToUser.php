<?php

namespace App\Models\Traits;

use App\Contracts\Authenticatable;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 多态关联用户特征
 */
trait MorphToUser
{
    /**
     * 关联用户
     *
     * @return MorphTo
     */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 设置关联用户
     *
     * @param  Authenticatable  $user
     * @return void
     */
    public function setUserAttribute(Authenticatable $user): void
    {
        $this->attributes['user_type'] = $user->getMorphClass();
        $this->attributes['user_id'] = $user->getKey();
    }
}
