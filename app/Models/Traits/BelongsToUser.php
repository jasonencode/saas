<?php

namespace App\Models\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * 用户关联模型特征
 *
 * @property int $user_id
 */
trait BelongsToUser
{
    /**
     * 设置关联用户
     *
     * @param  User  $user
     * @return void
     */
    public function setUserAttribute(User $user): void
    {
        $this->attributes['user_id'] = $user->getKey();
    }

    /**
     * 当前用户作用域
     *
     * @param  Builder  $builder
     * @return void
     */
    #[Scope]
    protected function ofCurrentUser(Builder $builder): void
    {
        if ($user = Auth::user()) {
            $builder->ofUser($user);
        }
    }

    /**
     * 关联用户
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)
            ->withoutGlobalScopes();
    }

    /**
     * 用户作用域
     *
     * @param  Builder  $builder
     * @param  User  $user
     * @return void
     */
    #[Scope]
    protected function ofUser(Builder $builder, User $user): void
    {
        $builder->where('user_id', $user->getKey());
    }
}
