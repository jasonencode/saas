<?php

namespace App\Http\Controllers\Traits;

use App\Models\Model;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

trait AuthorizesModelAccess
{
    /**
     * 检查当前用户是否有权限访问模型
     *
     * @param  Model  $model  模型实例
     * @param  bool  $hideExistence  无权限时是否以 404 隐藏资源存在性（读取场景防枚举）
     *
     * @throws AuthorizationException|ModelNotFoundException 无权限访问
     */
    protected function checkPermission(Model $model, bool $hideExistence = false): void
    {
        if (!$model->user || $model->user->is(Auth::user())) {
            return;
        }

        // 读取场景下以 404 代替 403，避免通过状态码探测他人资源是否存在
        if ($hideExistence) {
            throw (new ModelNotFoundException)->setModel($model::class, [$model->getKey()]);
        }

        throw new AuthorizationException;
    }
}
