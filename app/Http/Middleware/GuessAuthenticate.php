<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;

class GuessAuthenticate extends Authenticate
{
    /**
     * 认证用户
     *
     * @param  Request  $request  当前请求
     * @param  array  $guards  认证守卫列表
     */
    protected function authenticate($request, array $guards): void
    {
        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                $this->auth->shouldUse($guard);
            }
        }
    }
}
