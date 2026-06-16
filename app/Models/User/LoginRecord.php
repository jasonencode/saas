<?php

namespace App\Models\User;

use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 登录记录模型
 */
#[Unguarded]
class LoginRecord extends Model
{
    use Prunable;

    const UPDATED_AT = null;

    /**
     * 关联用户
     */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 获取可修剪的模型查询
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(180));
    }
}
