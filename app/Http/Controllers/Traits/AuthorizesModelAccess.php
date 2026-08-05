<?php

namespace App\Http\Controllers\Traits;

use App\Models\Model;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

trait AuthorizesModelAccess
{
    /**
     * 检查当前用户是否有权限访问模型
     *
     * @param  Model  $model  模型实例
     *
     * @throws AuthorizationException 无权限访问
     */
    protected function checkPermission(Model $model): void
    {
        if ($model->user && $model->user->isNot(Auth::user())) {
            throw new AuthorizationException;
        }
    }
}
