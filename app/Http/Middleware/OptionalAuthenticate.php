<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;

/**
 * 可选认证中间件
 *
 * 与 Authenticate 的区别：不抛认证异常——有token则设置守卫，无token放行。
 * 仅用于「登录/未登录返回不同数据」的公开接口（如商品详情的身份折扣价）。
 * 类名显式表达「可选」语义，避免被误用为强制鉴权（SYS-11）。
 */
class OptionalAuthenticate extends Authenticate
{
    /**
     * 认证用户（存在则选用，不存在则跳过）
     *
     * @param  Request  $request  当前请求
     * @param  array  $guards  认证守卫列表
     */
    protected function authenticate($request, array $guards): void
    {
        if (empty($guards)) {
            $guards = ['sanctum'];
        }

        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                $this->auth->shouldUse($guard);
            }
        }
    }
}
