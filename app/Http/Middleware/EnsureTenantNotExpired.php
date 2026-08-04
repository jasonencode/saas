<?php

namespace App\Http\Middleware;

use App\Filament\Tenant\Pages\TenantExpired;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 租户过期校验中间件
 *
 * 注册在 tenant 面板的 tenantMiddleware 中（位于 IdentifyTenant 之后），
 * 此时 Filament::getTenant() 已可用。当当前租户已过期
 * （expired_at 早于当前时间）时，把所有请求重定向到 TenantExpired 页面；
 * 已在该页面的请求直接放行，避免重定向循环。
 */
class EnsureTenantNotExpired
{
    /**
     * 处理 incoming 请求
     *
     * @param  Request  $request  当前请求
     * @param  Closure  $next  下一步处理
     *
     * @return Response 响应
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        if (!$tenant || !$tenant->isExpired()) {
            return $next($request);
        }

        $expiredUrl = TenantExpired::getUrl();

        // 已在过期页，放行；其余路径一律重定向到过期页
        if ($request->fullUrlIs($expiredUrl)) {
            return $next($request);
        }

        return redirect()->to($expiredUrl);
    }
}
