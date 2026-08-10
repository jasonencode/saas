<?php

namespace App\Models\User;

use App\Models\Model;
use App\Models\Traits\MorphToUser;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;

/**
 * 登录记录模型
 */
#[Unguarded]
class LoginRecord extends Model
{
    use MorphToUser,
        Prunable;

    const null UPDATED_AT = null;

    /**
     * 获取可修剪的模型查询
     *
     * @return Builder<LoginRecord>
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(180));
    }
}
