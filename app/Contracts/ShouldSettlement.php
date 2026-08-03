<?php

namespace App\Contracts;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property User $user
 */
/**
 * 可结算模型接口
 */
interface ShouldSettlement
{
    /**
     * 关联用户
     *
     * @return BelongsTo<User>
     */
    public function user(): BelongsTo;
}
