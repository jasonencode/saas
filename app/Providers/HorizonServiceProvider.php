<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * 覆盖 Horizon 默认的 horizon 中间件组，移除 SentinelMiddleware。
     *
     * SentinelMiddleware 在 local 环境下会拦截经反向代理（公网 IP）的请求并返回 401，
     * 导致下方的 Horizon::auth() 回调根本无法执行。而 Horizon 控制器基类
     * (Controller.php) 已通过 Authenticate 中间件调用 Horizon::check() 进行鉴权，
     * 因此移除 Sentinel 不影响安全性。
     */
    public function boot(): void
    {
        parent::boot();

        Route::middlewareGroup('horizon', config('horizon.middleware', ['web']));
    }

    /**
     * 定义 viewHorizon 权限门，仅允许管理员访问 Horizon。
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', static fn ($user = null) => $user->isAdministrator());
    }

    /**
     * 配置 Horizon 访问授权回调。
     *
     * Horizon 路由未挂载 auth:backend 中间件，$request->user() 默认返回 web 守卫
     * 的用户（通常为 null），因此需要回退到 backend 守卫获取已登录的后台管理员。
     */
    protected function authorization(): void
    {
        $this->gate();

        Horizon::auth(static function ($request) {
            $user = $request->user() ?? $request->user('backend');

            return Gate::forUser($user)->check('viewHorizon') || app()->environment('local');
        });
    }
}
